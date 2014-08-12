<?php

include_once('encoding.php');

ini_set('memory_limit', '1024M');
set_time_limit(300000);

class YourTwapperKeeper {

// sanitize data
    function sanitize($input)
    {
        if (is_array($input))
        {
            foreach ($input as $k => $i)
            {
                $output[$k] = $this->sanitize($i);
            }
        } else
        {
            if (get_magic_quotes_gpc())
            {
                $input = stripslashes($input);
            }
            $output = mysql_real_escape_string($input);
        }
        return $output;
    }

    function utf8_encode_deep($input)
    {
        if (is_array($input))
        {
            foreach ($input as $k => $i)
            {
                $output[$k] = $this->utf8_encode_deep($i);
            }
        }
        else
            $output = utf8_encode($input);

        return $output;
    }

    function utf8_decode_deep($input)
    {
        if (is_array($input))
        {
            foreach ($input as $k => $i)
            {
                $output[$k] = $this->utf8_encode_deep($i);
            }
        }
        else
            $output = utf8_decode($input);

        return $output;
    }

    function archiveExists($keyword, $type = -1)
    {
        global $db;
        global $tk;

        $r = mysql_query("select * from archives where keyword = '" . $keyword . "'" . (($type !== -1) ? " AND type = '$type'" : ""), $db->connection);

        if ($r == FALSE)
        {
            $tk->log(mysql_error());
            exit(-1);
        }

        if (!$r)
            return FALSE;

        if (mysql_num_rows($r) == 1)
            return mysql_fetch_assoc($r);

        mysql_free_result($r);

        return FALSE;
    }

    function getKeywords($num = 100)
    {
        global $db;

        $keywords = array();

        $q = "select LOWER(keyword) as keyword from archives order by keyword asc" . (($num > 0) ? " limit $num" : "");
        $result = mysql_query($q, $db->connection);

        while ($row = mysql_fetch_assoc($result))
            $keywords[] = strtolower($row["keyword"]);

        mysql_free_result($result);

        return $keywords;
    }

    function getUniformTags($num = 3)
    {
        global $db;

        $tags = array();

        $q = "select tags, count(tags) as num from archives where not tags = '' group by tags order by num desc" . (($num > 0) ? " limit $num" : "");
        $result = mysql_query($q, $db->connection);

        while ($row = mysql_fetch_assoc($result))
        {
            if (strpos($row["tags"], ",") >= 0)
            {
                $parts = explode(",", $row["tags"]);
                foreach ($parts as $part)
                {
                    if (!in_array(strtolower($part), $tags))
                        $tags[] = strtolower($part);
                }
            }
            else if (!in_array(strtolower($part), $tags))
                $tags[] = strtolower($row["tags"]);
        }

        mysql_free_result($result);

        return $tags;
    }

    // list archives
    function listArchivesWithCondition($condition)
    {
        global $db;

        $q = "select * from archives where $condition";

        $r = mysql_query($q, $db->connection);
        $count = 0;
        while ($row = mysql_fetch_assoc($r))
        {
            $count++;
            $response['results'][] = $row;
        }

        $response['count'] = $count;

        mysql_free_result($r);

        return $response;
    }

    // list archives
    function listArchive($id = false, $keyword = false, $description = false, $tags = false, $screen_name = false, $debug = false)
    {
        global $db;

        $q = "select * from archives where type IN (1,2,3)";

        if ($id)
        {
            $q .= " and id = '$id'";
        }

        if ($keyword)
        {
            $q .= " and keyword like '%$keyword%'";
        }

        if ($description)
        {
            $q .= " and description like '%$description%'";
        }

        if ($tags)
        {
            $q .= " and tags like '%$tags%'";
        }

        if ($screen_name)
        {
            $q .= " and screen_name like '%$screen_name%'";
        }

        if (!$id && !$keyword && !$description && !$tags && !$screen_name && !$debug)
            $limit = "limit 50";
        else
            $limit = "";

        $r = mysql_query($q . " order by count desc $limit", $db->connection);
        $count = 0;
        while ($row = mysql_fetch_assoc($r))
        {
            $count++;
            $response['results'][] = $row;
        }

        $response['count'] = $count;

        mysql_free_result($r);

        return $response;
    }

    function getStats()
    {
        global $db;

        // TODO this is not the most efficient way to fetch latest entry?
        $r = mysql_query("select * from statistics ORDER BY id DESC LIMIT 1", $db->connection);
        $s = mysql_fetch_assoc($r);

        $stats = array();
        if (mysql_num_rows($r) == 1)
        {
            $stats[] = "Fetched <span style='font-weight:bold'>" . $s["num_tweets"] . " tweets </span> in total.";
            $stats[] = "Fetching <span style='font-weight:bold'>" . $s["avg_tweets"] . " tweets per minute.</span>";
            $stats[] = "<span style='font-weight:bold'>Track load: " . $s["track_load"] . " % -- " . "Follow load: " . $s["follow_load"] . " % </span>";
            $stats[] = "Tracking <span style='font-weight:bold'>" . $s["num_hashtags"] . " hashtags, " . $s["num_follows"] . " users, and " . $s["num_conversations"] . " conversations.</span>";
        }

        mysql_free_result($r);

        return $s;
    }

    function getHistoryStats($num = 12)
    {
        global $db;

        // TODO this is not the most efficient way to fetch latest entry?
        $r = mysql_query("select * from statistics ORDER BY id DESC LIMIT $num", $db->connection);

        $labels = array();
        $values = array();
        if (mysql_num_rows($r) == $num)
        {
            $index = $num - 1;
            for ($i = 0; $i < $num; $i++)
            {
                $labels[$i] = 0;
                $values[$i] = 0;
            }

            while ($record = mysql_fetch_assoc($r))
            {
                $labels[$index] = date("'ga'", $record["created_at"]);
                $values[$index] = $record["avg_tweets"];

                $index--;
            }
        }

        mysql_free_result($r);

        return [$labels, $values];
    }

// create archive
// archive types stand for the different archiving possibilities
// (1 = keyword tracking, 2 = hashtag tracking, 3 = user tracking, 4 = user conversation tracking )
    function createArchive($keyword, $description, $tags, $screen_name, $user_id, $type = 0, $track_id = NULL, $debug = false)
    {
        global $db;

        $response = array();
        
        // Remove whitespaces and quotes
        $keyword = trim(trim($keyword), '"');
        $description = trim(trim($description), '"');
        $tags = trim(trim($tags), '"');

        // Ignore '/' (used as indication of 'unknown')
        if (trim($keyword) === "/")
        {
            $response[0] = "Ignore '/'.";
            return $response;
        }
        
        // Remove keyword's first character if it equals '@' or '#'.
        $keyword = trim(($keyword[0] == "@" || $keyword[0] == "#") ? substr($keyword, 1) : $keyword);       
        

        $q = "select * from archives where keyword = '$keyword' and (type='$type' or (type IN (4,5) and $type=3))";

        $r = mysql_query($q, $db->connection);
        if (mysql_num_rows($r) > 0)
        {
            $response[0] = "Archive for '" . $keyword . "' already exists.";
            $result = mysql_fetch_assoc($r);

            $oldTags = $result['tags'];
            if (strcasecmp($tags, $oldTags) != 0)
            {
                if (strlen($oldTags) > 0)
                    $oldTags .= ',';

                $newTags = ($oldTags . $tags);
            }
            else
                $newTags = $oldTags;

            // Check if type should be upgraded (from 4 or 5 to 3)
            if (($result['type'] == 4 || $result['type'] == 5) && $type == 3)
                $newType = 3;
            else
                $newType = $result['type'];
            // Modify tag so archive can be properly listed
            $q = "update archives set description = '$description', tags = '$newTags', type = '$newType' where keyword = '$keyword'";
            mysql_query($q, $db->connection);

            return($response);
        }

        mysql_free_result($r);

        if (strlen($keyword) < 1 || strlen($keyword) > 30)
        {
            $response[0] = (($type == 1) ? "Keyword field" : ($type == 2) ? "Hashtag field" : "User field" ) . " cannot be blank.";
            return($response);
        }

        if (strlen($keyword) > 30)
        {
            $response[0] = "Input value must be less than 30 characters.";
            return($response);
        }

        // If keyword is user, get user information.
        if ($type == 3)
            $user = $this->addUser($keyword);
        else if ($type == 4)
            $user = $this->addHollowUser($keyword, $track_id);
        else
            $user = 'NULL';

        $q = "insert into archives values ('','$keyword', '$user', '$type', '$description','$tags','$screen_name','$user_id','','" . time() . "', 0, 0)";
        mysql_query($q, $db->connection);
        $lastid = mysql_insert_id();
        //        `in_reply_to_status_id` varchar(100) NOT NULL,
        $create_table = "CREATE TABLE IF NOT EXISTS `z_$lastid` (
        `archivesource` varchar(100) NOT NULL,
        `text` varchar(1000) NOT NULL,
        `to_user_id` varchar(100) NOT NULL,
        `to_user` varchar(100) NOT NULL,             
        `from_user_id` varchar(100) NOT NULL,
        `from_user` varchar(100) NOT NULL,
        `original_user_id` varchar(100) NOT NULL,
        `original_user` varchar(100) NOT NULL,
        `id` varchar(100) NOT NULL,
        `in_reply_to_status_id` varchar(100) NOT NULL,
        `iso_language_code` varchar(10) NOT NULL,
        `source` varchar(250) NOT NULL,
        `profile_image_url` varchar(250) NOT NULL,
        `geo_type` varchar(30) NOT NULL,
        `geo_coordinates_0` double NOT NULL,
        `geo_coordinates_1` double NOT NULL,
        `created_at` varchar(50) NOT NULL,        
        `time` int(11) NOT NULL,
        `updated_at` int(11) NULL,
        `favorites` int(11) NULL,
        `retweets` int(11) NULL,
        FULLTEXT `full` (`text`),
        INDEX `source` (`from_user`),
        INDEX `from_user` (`from_user`),
        INDEX `iso_language_code` (`iso_language_code`),
        INDEX `geo_type` (`geo_type`),
        INDEX `id` (`id`),
        UNIQUE KEY (`id`),
        INDEX `time` (`time`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1";

        mysql_query($create_table, $db->connection) or die(mysql_error());

        $response['id'] = $lastid;
        $response['type'] = $type;
        $response[0] = "Archive has been created.";

        return($response);
    }

    // helper function
    function addUser($screen_name)
    {
        global $db;

        // check if user does not exist in db        
        $q = "select id from twitter_users where screen_name = '$screen_name' LIMIT 1";
        $r = mysql_query($q, $db->connection);
        if (mysql_num_rows($r) > 0)
            return mysql_fetch_assoc($r)["id"];
        else
        {
            // insert user into database
            $q = "insert into twitter_users (screen_name) values ('" . $screen_name . "')";
            mysql_query($q, $db->connection);

            return mysql_insert_id();
        }
        mysql_free_result($r);
    }

    function addHollowUser($screen_name, $user_id)
    {
        global $db;

        // check if user does not exist in db        
        $q = "select id from twitter_users where screen_name = '$screen_name' LIMIT 1";
        $r = mysql_query($q, $db->connection);
        if (mysql_num_rows($r) > 0)
            return mysql_fetch_assoc($r)["id"];
        else
        {
            // insert user into database
            $q = "insert into twitter_users (screen_name, twitter_id, flag) values ('$screen_name', '$user_id', 1)";
            mysql_query($q, $db->connection);

            return mysql_insert_id();
        }
        mysql_free_result($r);
    }

    function cmpTweets($a, $b)
    {
        return $a["time"] - $b["time"];
    }

    function cmpConversations($a, $b)
    {
        if (count($a) > 0 && count($b) > 0)
            return $a[0]["time"] - $b[0]["time"];
        else if (count($a) > 0)
            return 1;
        else if (count($b) > 0)
            return -1;
        else
            0;
    }

    function getUser($screen_name)
    {
        global $db;

        // check if user does not exist in db        
        $q = "select * from twitter_users where screen_name like '$screen_name' LIMIT 1";
        $r = mysql_query($q, $db->connection);
        if (mysql_num_rows($r) > 0)
        {
            $result = mysql_fetch_assoc($r);
            mysql_free_result($r);
            return $result;
        }
        else
            return NULL;
    }

    function getTweetsFromArchives($archives, $start = false, $end = false, $limit = false, $orderby = false, $nort = false, $from_user = false, $text = false, $lang = false, $max_id = false, $since_id = false, $offset = false, $lat = false, $long = false, $rad = false, $debug = false, $retweets = false, $favorites = false, $include_reactions = false)
    {
        $response = array();
        $tweets = array();
        $pool = array();
        $ids = array();
        $conversations = array();
        $conversations_list_tweets = array();

        $total_num_to_process = 0.0;
        foreach ($archives as $archive)
            $total_num_to_process += $archive['count'];
        $total = 0.0;

        foreach ($archives as $archive)
        {
            $result = $this->getTweets($archive['id'], $archive['type'], $start, $end, false, $orderby, false, ($from_user) ? $archive['keyword'] : false, $text, $lang, $max_id, $since_id, $offset, $lat, $long, $rad, $debug, $retweets, $favorites);

            foreach ($result as $r)
            {
                $r['description'] = $archive['description'];
                $r['tags'] = $archive['tags'];

                $tweet_text = $this->sanitize(trim($r['text']));

                if ($include_reactions)
                {
                    if (strpos($tweet_text, "RT @") !== 0)
                    {
                        $conversation = $this->getConversation($r['id']);

                        // find all related other conversations
                        $related_conversations = array();
                        foreach ($conversation as $conv_tweet)
                        {
                            if (array_key_exists($conv_tweet['id'], $ids) && !in_array($ids[$conv_tweet['id']], $related_conversations))
                                $related_conversations[] = $ids[$conv_tweet['id']];
                        }

                        if (count($related_conversations) === 0)
                        {
                            // add new conversation
                            $conversations[] = $conversation;
                            $conversation_id = count($conversations) - 1;
                            $ids[$r['id']] = $conversation_id; // set to id of conversation it belongs to 
                            // list tweet ids in conversation
                            $list_tweets = array();
                            foreach ($conversation as $conv_tweet)
                            {
                                $list_tweets[$conv_tweet['id']] = 1;
                                $ids[$conv_tweet['id']] = $conversation_id;
                            }

                            $conversations_list_tweets[] = $list_tweets;
                        } else
                        {
                            // merge conversations
                            $conversation_id = array_shift($related_conversations);
                            $all_tweets = $conversation;
                            foreach ($related_conversations as $related)
                                $all_tweets = array_merge($all_tweets, $conversations[$related]);

                            foreach ($all_tweets as $conv_tweet)
                            {
                                if (!array_key_exists($conv_tweet['id'], $conversations_list_tweets[$conversation_id]))
                                {
                                    // add tweet to conversation
                                    $conversations_list_tweets[$conversation_id][$conv_tweet['id']] = 1;
                                    $ids[$conv_tweet['id']] = $conversation_id;
                                    $conversations[$conversation_id][] = $conv_tweet;
                                }
                            }

                            // remove merged conversations
                            foreach ($related_conversations as $related)
                            {
                                $conversations[$related] = array();
                                $conversations_list_tweets[$related] = array();
                            }
                        }
                    }
                } else
                {
                    if (!array_key_exists($r['id'], $ids))
                    {
                        if ($nort)
                        {
                            if (strpos($tweet_text, "RT @") === 0)
                            {
                                $key = trim(substr($tweet_text, strpos($tweet_text, ":") + 1));

                                if (!array_key_exists($key, $tweets))
                                {
                                    if (!array_key_exists($key, $pool))
                                    {
                                        $pool[$key] = $r;
                                    } else if ($pool[$key]["time"] > $r["time"])
                                        $pool[$key] = $r;
                                }
                            }
                            else
                            {
                                $tweets[$tweet_text] = 1;
                                $response[] = $r;
                            }
                        }
                        else
                            $response[] = $r;

                        $ids[$r['id']] = 1;
                    }
                }
                $total++;
            }
            echo '<script type="text/javascript">parent.progress(' . round(($total / $total_num_to_process * 100)) . ');</script>';
        }

        if ($nort)
        {
            foreach ($pool as $key => $tweet)
            {
                if (!array_key_exists($key, $tweets))
                    $response[] = $tweet;
            }
        }

        if (!$include_reactions)
            usort($response, array($this, "cmpTweets"));
        else
        {
            // sort tweets in conversation
            foreach ($conversations as $key => $conversation)
                usort($conversations[$key], array($this, "cmpTweets"));

            // sort conversations based on root tweet
            usort($conversations, array($this, "cmpConversations"));

            // flatten 2d array
            $response = array();
            foreach ($conversations as $conversation)
                $response = array_merge($response, $conversation);
        }

        if ($limit)
            $response = array_slice($response, 0, $limit);

        return $response;
    }

    function getTweets($id, $type, $start = false, $end = false, $limit = false, $orderby = false, $nort = false, $from_user = false, $text = false, $lang = false, $max_id = false, $since_id = false, $offset = false, $lat = false, $long = false, $rad = false, $debug = false, $retweets = false, $favorites = false)
    {
        global $db;

        $response = array();
        $start = $this->sanitize($start);
        $end = $this->sanitize($end);
        $limit = $this->sanitize($limit);
        $orderby = $this->sanitize($orderby);
        $nort = $this->sanitize($nort);
        $from_user = $this->sanitize($from_user);
        $text = $this->sanitize($text);
        $lang = $this->sanitize($lang);
        $offset = $this->sanitize($offset);
        $max_id = $this->sanitize($max_id);
        $since_id = $this->sanitize($since_id);
        $lat = $this->sanitize($lat);
        $long = $this->sanitize($long);
        $rad = $this->sanitize($rad);
        $retweets = $this->sanitize($retweets);
        $favorites = $this->sanitize($favorites);

        $q = "select * from z_" . $id . " where 1";

        // build param query
        $qparam = '';

        if ($start > 0)
            $qparam .= " and time >= $start";

        if ($end > 0)
            $qparam .= " and time <= $end";

        if ($nort == 1)
            $qparam .= " and text not like 'RT%'";

        if ($from_user)
            $qparam .= " and from_user = '$from_user'";

        if ($text)
            $qparam .= " and text like '%$text%'";

        if ($lang)
            $qparam .= " and iso_language_code='$lang'";

        if ($since_id)
            $qparam .= " and id >= $since_id";

        if ($max_id)
            $qparam .= " and id <= $max_id";

        if ($retweets || $favorites)
            $qparam .= " and (" . (($retweets) ? "retweets >= " . $retweets : "") . (($retweets && $favorites) ? " and " : "") . (($favorites) ? "favorites >= " . $favorites : "") . ")";


        if ($lat OR $long OR $rad)
        {

            $R = 6371;  // earth's radius, km

            $maxLat = $lat + rad2deg($rad / $R);
            $minLat = $lat - rad2deg($rad / $R);

            $maxLon = $lon + rad2deg($rad / $R / cos(deg2rad($lat)));
            $minLon = $lon - rad2deg($rad / $R / cos(deg2rad($lat)));

            $qparam .= " and geo_coordinates_0 > $minLat and geo_coordinates_0 < $maxLat and geo_coordinates_1 > $minLon and geo_coordinates_1 < $maxLon";
        }

        if ($orderby == "a")
        {
            $qparam .= " order by time asc";
        } else
        {
            $qparam .= " order by time desc";
        }

        if ($limit)
        {
            $qparam .= " limit $limit";
        }

        $query = $q . $qparam;

        $r = mysql_query($query, $db->connection);

        $response = array();
        while ($row = mysql_fetch_assoc($r))
        {
            // Check original tweet if some fields are missing
            if ($row['retweets'] == '' || $row['retweets'] === FALSE || $row['favorites'] == '' or $row['favorites'] === FALSE)
            {
                $temprow = $this->getOriginalTweet($row["id"]);
                if ($temprow != FALSE)
                {
                    $row["retweets"] = $temprow["retweets"];
                    $row["favorites"] = $temprow["favorites"];
                }
            }

            $response[] = $row;
        }
        return $response;
    }

    /**
     * Reconstruct conversation linked to given tweet.
     * @global type $dbc
     * @param type $tweetID
     * @return set of tweets
     */
    function getConversation($tweetID)
    {
        global $db;

        // Get tweet data
        $r = mysql_query("select original_archive from smart_tweets where tweet_id = '$tweetID'");
        if (mysql_num_rows($r) != 0)
        {
            $archive = mysql_fetch_assoc($r)['original_archive'];
            $r_sub = mysql_query("select * from z_$archive where id = '$tweetID'");
            $tweet = mysql_fetch_assoc($r_sub);
            mysql_free_result($r_sub);
        }
        else
            return FALSE;

        mysql_free_result($r);

        // Find root if tweet is a reply itself
        if (!empty($tweet["in_reply_to_status_id"]))
            $root_user = $this->findRootTweetUser($tweet['in_reply_to_status_id']);
        else
            $root_user = $tweet['from_user'];


        // Get conversation related to this archive and tweet.
        $subq = "select id from archives where keyword = '" . $root_user . "'";
        $subr = mysql_query($subq, $db->connection);

        $temptweets = array();
        if (mysql_num_rows($subr) == 1)
        {
            $conversation_archive = mysql_fetch_assoc($subr)["id"];
            $subsubq = "select * from z_" . $conversation_archive . " where 1 order by time asc";
            $subsubr = mysql_query($subsubq, $db->connection);

            while ($subsubrow = mysql_fetch_assoc($subsubr))
            {
                // Check original tweet if some fields are missing
                if ($subsubrow['retweets'] == '' || $subsubrow['retweets'] === FALSE || $subsubrow['favorites'] == '' or $subsubrow['favorites'] === FALSE)
                {
                    $temprow = $this->getOriginalTweet($subsubrow["id"]);
                    if ($temprow != FALSE)
                    {
                        $subsubrow["retweets"] = $temprow["retweets"];
                        $subsubrow["favorites"] = $temprow["favorites"];
                    }
                }
                $temptweets[$subsubrow['id']] = $subsubrow;
            }
        }
        mysql_free_result($subr);
        return array_merge($this->findPath($tweet['in_reply_to_status_id'], $temptweets, false), array($tweet), $this->findPath($tweet['id'], $temptweets, true));
    }

    /**
     * Find all tweets that are linked to tweet with $id, either before or after tweet was posted.
     * @param type $id Root tweet
     * @param type $tweets Represents sorted (oldest tweet first) list of all possible replies
     * @param type $down Defines which way should be searched: up or down
     */
    function findPath($id, $tweets, $down)
    {
        $path = array();
        $pointer = $id;

        if (!$down)
        {
            while ($pointer != "")
            {
                if (!array_key_exists($pointer, $tweets))
                    return $path;

                array_unshift($path, $tweets[$pointer]);
                $pointer = $tweets[$pointer]['in_reply_to_status_id'];
            }
        }
        else
        {
            if (empty($tweets))
                return $path;

            $pointers = array();
            $pointers[$id] = 1;
            foreach ($tweets as $reply)
            {
                if (array_key_exists($reply["in_reply_to_status_id"], $pointers))
                {
                    $path[] = $reply;
                    $pointers[$reply['id']] = 1;
                }
            }
        }
        return $path;
    }

    function findRootTweetUser($tweetID)
    {

        $in_reply_to = $tweetID;
        while ($in_reply_to != "")
        {
            $rootID = $in_reply_to;

            $r = mysql_query("select original_archive from smart_tweets where tweet_id = '$in_reply_to'");
            if (mysql_num_rows($r) != 0)
            {
                $archive = mysql_fetch_assoc($r)['original_archive'];
                $subr = mysql_query("select in_reply_to_status_id, from_user from z_$archive where id = '$rootID'");
                $result = mysql_fetch_assoc($subr);
                $rootUser = $result['from_user'];
                $in_reply_to = $result['in_reply_to_status_id'];

                mysql_free_result($subr);
            }
            else
                $in_reply_to = "";

            mysql_free_result($r);
        }
        return $rootUser;
    }

    function getOriginalTweet($tweetID)
    {
        global $db;

        $r = mysql_query("SELECT original_archive, original_id FROM smart_tweets WHERE tweet_id = '" . $tweetID . "'", $db->connection);

        if (mysql_num_rows($r) == 1)
        {
            $row = mysql_fetch_assoc($r);

            $originalID = $row['original_id'];

            if (!empty($originalID))
            {
                $r2 = mysql_query("SELECT original_archive FROM smart_tweets WHERE tweet_id = '$originalID'", $db->connection);
                $archiveID = (mysql_num_rows($r2) == 1) ? mysql_fetch_assoc($r2)['original_archive'] : $row['original_archive'];
                mysql_free_result($r2);
            }
            else
                $archiveID = $row['original_archive'];


            $r3 = mysql_query("SELECT * FROM z_$archiveID WHERE id = '$tweetID' OR (NOT '$originalID' = '' AND id = '$originalID')", $db->connection);            
            $result = mysql_fetch_assoc($r3);


            mysql_free_result($r3);
            mysql_free_result($r);

            return $result;
        }

        mysql_free_result($r);

        return FALSE;
    }

    function extractTweetData($jsonobject)
    {
        global $tk;

        $value = get_object_vars($jsonobject);
        if (array_key_exists('retweeted_status', $value))
        {
            $orig = get_object_vars($value['retweeted_status']);
            $orig_user = get_object_vars($orig["user"]);
            $orig_time = strtotime($orig["created_at"]);

            $text = "RT @" . $orig_user['screen_name'] . ": " . $tk->sanitize($orig['text']);
            $orig_id = $orig["id"];
        } else
        {
            $orig_user["id"] = "";
            $orig_user["screen_name"] = "";
            $orig_time = strtotime($value['created_at']);
            $orig_id = 0;
            $text = $tk->sanitize($value['text']);
        }

        // extract data
        //extract($value,EXTR_PREFIX_ALL,'temp');

        $tweet = array();

        $tweet["text"] = $text;
        $tweet["to_user"] = $value['in_reply_to_screen_name'];
        $tweet["to_user_id"] = (string) $value['in_reply_to_user_id'];
        $tweet["from_user"] = $value['user']->screen_name;
        $tweet["from_user_id"] = (string) $value['user']->id;
        $tweet["id"] = $value['id_str'];
        $tweet["in_reply_to_status_id"] = $value['in_reply_to_status_id_str'];
        $tweet["original_id"] = $orig_id;
        $tweet["original_user"] = $orig_user['id'];
        $tweet["original_user_id"] = $orig_user['screen_name'];
        $tweet["iso_language_code"] = $value['user']->lang;
        $tweet["source"] = $value['source'];
        $tweet["profile_image_url"] = $value['user']->profile_image_url;
        $tweet["created_at"] = $value['created_at'];
        $tweet["time"] = strtotime($value['created_at']);
        $tweet["original_time"] = $orig_time;


        // extract geo information               
        if ($value['geo'] != NULL)
        {
            $geo = get_object_vars($value['geo']);
            $tweet['geo_type'] = $geo['type'];
            $tweet['geo_coordinates_0'] = $geo['coordinates'][0];
            $tweet['geo_coordinates_1'] = $geo['coordinates'][1];
        } else
        {
            $tweet['geo_type'] = NULL;
            $tweet['geo_coordinates_0'] = 0;
            $tweet['geo_coordinates_1'] = 0;
        }

        return $tweet;
    }

    function isReply($replyTweet, $origTweet)
    {
        global $time_to_track_user;

        if ($replyTweet["in_reply_to_status_id"] == $origTweet["id"])
            return TRUE;
        else if ($replyTweet["in_reply_to_status_id"] == "")
        {
            if ($replyTweet["to_user"] == $origTweet["from_user"])
            {
                //print "TIME DIFF: ". ((int)$replyTweet["time"] - (int)$origTweet["time"]) . " seconds. MAX($time_to_track_user) <br/>";
                if ($replyTweet["time"] - $origTweet["time"] > 0 && $replyTweet["time"] - $origTweet["time"] < $time_to_track_user)
                    return TRUE;
            }
        }
        return FALSE;
    }

// delete archive
    function deleteArchive($id)
    {
        global $db;
        $q = "delete from archives where id = '$id'";
        $r = mysql_query($q, $db->connection);

        $q = "drop table if exists z_$id";
        $r = mysql_query($q, $db->connection);

        $response[0] = "Archive has been deleted.";
        return($response);
    }

// update archive
    function updateArchive($id, $description, $tags)
    {
        global $db;
        $q = "update archives set description = '$description' where id = '$id'";
        $r = mysql_query($q, $db->connection);
        $q = "update archives set tags = '$tags' where id = '$id'";
        $r = mysql_query($q, $db->connection);
        $response[0] = "Archive has updated.";
        return($response);
    }

// check status of archiving processes	
    function statusArchiving($process_array)
    {
        global $db;
        // If PIDs > 0 - we are considered running
        $running = TRUE;
        $pids = '';
        $shouldBeRunning = 1;

        $result = array();
        $result[3] = array();

        foreach ($process_array as $key => $value)
        {
            $q = "select pid from processes where process = '$value'";
            $r = mysql_query($q, $db->connection);
            $r = mysql_fetch_assoc($r);
            $pid = $r['pid'];

            unset($PROC);
            exec("ps $pid", $PROC);

            if ($pid == 0)
            {
                $running = FALSE;
                $shouldBeRunning = FALSE;
            }

            if (count($PROC) < 2)
            {
                $running = FALSE;
                $pids .= "<span style='color:red'>" . $pid . "</span>, ";
                $result[3][] = $pid;
            }
            else
                $pids .= $pid . ", ";
        }
        $pids = substr($pids, 0, -2);


        if ($running == FALSE || count($process_array) == 0)
        {
            $result[0] = FALSE;
            if ($shouldBeRunning == 1 && count($process_array) !== 0)
            {
                $result[1] = "Archiving processes have died.  (PIDS = $pids)";
                $result[2] = 1;
            } else
            {
                $result[1] = "Archiving processes are NOT running.";
                $result[2] = 0;
            }
        } else
        {
            $result[0] = TRUE;
            $result[1] = "Archiving processes are running. (PIDS = $pids)";
        }

        return($result);
    }

    // check status of archiving processes	
    function statusLiveArchiving()
    {
        global $db;
        $processes = array();
        $r = mysql_query("select process from processes where live = 1", $db->connection);
        while ($a = mysql_fetch_assoc($r))
            $processes[] = $a["process"];

        mysql_free_result($r);

        return $this->statusArchiving($processes);
    }

    function trackConversation($base_archive, $tweet)
    {
        global $db;
        global $tk;
        global $tk_twitter_username;
        global $tk_twitter_user_id;

        $archive = $this->archiveExists($tweet['from_user']);
        if ($archive !== FALSE)
        {
            // If archive type is 5 make it active again.
            if ($archive["type"] == 5)
                mysql_query("update archives set type = '4' where id = '" . $archive["id"] . "'", $db->connection);
        }
        else
        // Create new 'conversation' archive
            $archive = $this->createArchive($tweet['from_user'], "conversation tracking", "", $tk_twitter_username, $tk_twitter_user_id, 4, $tweet['from_user_id']);

        // check if conversation record exists      
        if ($archive["type"] == 4 || $archive["type"] == 5)
        {
            $result = mysql_query("select * from conversations where archive = " . $archive["id"], $db->connection);
            if (mysql_num_rows($result) == 0)
            // Create conversation archive
                mysql_query("insert into conversations (archive, tweet_id, created_at) values (" . $archive["id"] . ", '" . $tweet['id'] . "', UNIX_TIMESTAMP())", $db->connection);
            else
            // Update conversation archive
                mysql_query("update conversations set tweet_id = '" . $tweet['id'] . "', created_at = UNIX_TIMESTAMP() where archive = '" . $archive["id"] . "'", $db->connection);
            mysql_free_result($result);
        }
    }

    function untrackConversations($archives)
    {
        global $db;
        global $function_log;

        $q_old_users = "update archives set type = 5, tracked_by = 0, followed_by = 0 where type = 4 AND id IN ($archives)";
        mysql_query($q_old_users, $db->connection);
        $this->log(mysql_error($db->connection), 'mysql-untrackConversations-update', $function_log);

        $q = "delete from conversations where archive IN ($archives)";
        mysql_query($q, $db->connection);
        $this->log(mysql_error($db->connection), 'mysql-untrackConversations-delete', $function_log);
    }

    function untrackConversation($archive)
    {
        global $db;
        global $function_log;

        $q_old_users = "update archives set type = 5, tracked_by = 0, followed_by = 0 where type = 4 AND id = '$archive'";
        mysql_query($q_old_users, $db->connection);
        $this->log(mysql_error($db->connection), 'mysql-untrackConversation-update', $function_log);

        $q = "delete from conversations where archive = '$archive'";
        mysql_query($q, $db->connection);
        $this->log(mysql_error($db->connection), 'mysql-untrackConversation-delete', $function_log);
    }

    // kill archiving process
    function killProcess($pid)
    {
        $command = 'kill -9 ' . $pid;
        exec($command);
    }

    // start archiving process
    function startProcess($cmd)
    {
        $command = "$cmd > log/processes_error_log 2>log/processes_error_log & echo $!";
        exec($command, $op);
        $pid = (int) $op[0];
        return ($pid);
    }

    function log($message, $level = 'notice', $file = 'tk_log')
    {
        if ($message != "")
            file_put_contents($file, gmdate("d-M-Y H:i:s") . "\t[$level]\t" . $message . "\n", FILE_APPEND);
    }

    function expandShortUrl($url)
    {
        global $db;

        $result = mysql_query("select expanded_url from urls where shortened_url = '$url'", $db->connection);
        if ($result != false && mysql_num_rows($result) == 1)
            return mysql_fetch_assoc($result)["expanded_url"];

        mysql_free_result($result);

        $headers = get_headers($url, 1);

        if (!empty($headers['Location']) || !empty($headers['location']))
        {
            $headers['Location'] = (array) ((empty($headers['Location'])) ? $headers['location'] : $headers['Location']);
            $new_url = array_pop($headers['Location']);

            // insert in db
            mysql_query("insert into urls values(0,'$url', '$new_url')", $db->connection);

            return $new_url;
        }
        return $url;
    }

    function insertTweet($table_id, $tweet, $type, $reason = '', $log_file = 'log/function_log')
    {
        global $db;
        global $time_to_track_user;
        global $track_conversations;

        $q = "insert into z_$table_id values ('twitter-$reason','" . $this->sanitize($tweet['text']) . "','" . ((string) $tweet['to_user_id']) . "','" . $tweet['to_user'] . "','" . ((string) $tweet['from_user_id']) . "','" . $tweet['from_user'] . "','" . ((string) $tweet['original_user_id']) . "','" . $tweet['original_user'] . "','" . ((string) $tweet['id']) . "','" . ((string) $tweet['in_reply_to_status_id']) . "','" . $tweet['iso_language_code'] . "','" . $tweet['source'] . "','" . $tweet['profile_image_url'] . "','" . $tweet['geo_type'] . "','" . $tweet['geo_coordinates_0'] . "','" . $tweet['geo_coordinates_1'] . "','" . $tweet['created_at'] . "','" . $tweet['time'] . "', NULL, NULL, NULL)";
        mysql_query($q, $db->connection);
        $this->log(mysql_error($db->connection), 'mysql-insertTweet-insert', $log_file);

        if ($tweet['original_time'] > 0)
            $time = $tweet['original_time'];
        else
            $time = $tweet['time'];

        $duplicate = $this->addSmartTweet($tweet, $table_id, $log_file);

        // Update is only required when tweet is not older than threshold and not registered already (duplicates)    
        if (!$duplicate)
        {
            $q = "insert into new_tweets values('" . ((string) $tweet['id']) . "', $table_id, '" . $time . "', UNIX_TIMESTAMP(), -1)";
            mysql_query($q, $db->connection);
            $this->log(mysql_error($db->connection), 'mysql-insertTweet-newtweets', $log_file);
        }

        // Track conversation if not too old and dealing with hashtagged tweet               
        if (time() - $time < $time_to_track_user && $type == 2 && $track_conversations)
            $this->trackConversation($table_id, $tweet);

        return TRUE;
    }

    function addSmartTweet($tweet, $table_id, $log_file = 'log/function_log')
    {
        global $db;
        global $function_log;

        $duplicate = FALSE;

        if (isset($tweet['original_id']) && $tweet['original_id'] != '')
        {
            mysql_query("insert into smart_tweets values (0,'" . $tweet['id'] . "','" . $tweet['original_id'] . "','" . $table_id . "', 0, 1)", $db->connection);
            $this->log(mysql_error($db->connection), 'mysql-addSmartTweet-insert-retweet', $log_file);

            if (mysql_error($db->connection) != '')
                $duplicate = TRUE;
        } else
        {
            mysql_query("insert into smart_tweets values (0,'" . $tweet['id'] . "',NULL,'" . $table_id . "', 0, 0)", $db->connection);
            $this->log(mysql_error($db->connection), 'mysql-addSmartTweet-insert', $log_file);

            if (mysql_error($db->connection) != '')
                $duplicate = TRUE;
        }
        return $duplicate;
    }

    function saveExport($data)
    {
        global $db;

        // clear export table
        mysql_query("truncate table export", $db->connection);

        foreach ($data as $key => $element)
        {
            $value = $this->sanitize(json_encode(Encoding::fixUTF8($element), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            mysql_query("insert into export values (0, '$key', '$value')", $db->connection);

            if (empty($value))
                print var_dump($element);
        }

        return TRUE;
    }

    function getExportData()
    {
        global $db;

        $data = array();

        $result = mysql_query("select * from export");

        while ($record = mysql_fetch_assoc($result))
            $data[$record['key']] = json_decode($record['value'], true);

        mysql_free_result($result);

        return $data;
    }

}

$tk = new YourTwapperKeeper;
?>
