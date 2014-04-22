<?php

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// setup values
$crawl_log_file = "log/crawl_process_log";
$pid = getmypid();
$sleep = $twitter_api_sleep_sec;
$count = 0;

// Setup connection
$connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $tk_oauth_token, $tk_oauth_token_secret);
$connection->useragent = $youtwapperkeeper_useragent;

date_default_timezone_set("Europe/Brussels");

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

while (TRUE)
{
    // Query for archives 
    $q_archives = "select * from archives where type in (1,2,3) order by count desc";
    $r_archives = mysql_query($q_archives, $db->connection);
    $counting = 0;
    
    while ($row_archives = mysql_fetch_assoc($r_archives))
    {
        $tk->log($counting++ .  ".   crawling: " . $row_archives['id'] . " - " . $row_archives['keyword'], "", $crawl_log_file);
        $num_inserted = 0;
        // Loop for 15 pages
        $max_id = NULL;

        for ($page_counter = 1; $page_counter <= 15; $page_counter = $page_counter + 1)
        {

            // sleep for rate limiting
            //echo "sleep = $sleep\n";
            sleep($sleep);

            //echo "****TIME AROUND = " . $page_counter . "****\n";

            $type = ($row_archives['type'] == 1 ) ? "" : ($row_archives['type'] == 2) ? "#" : "@";

            if ($max_id == NULL)
            {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100, 'result_type' => 'recent'));
                //echo "NO - no max_id is not set\n";
            } else
            {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100, 'max_id' => $max_id, 'result_type' => 'recent'));
                //echo "YES - max_id is set\n";
            }


            $searchresult = get_object_vars($search);
            $count = count($searchresult['statuses']);

            // parse results
            foreach ($searchresult['statuses'] as $key => $value)
            {
                $value = get_object_vars($value);

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
                $tweet["original_user"] = $orig_user['id'];
                $tweet["original_user_id"] = $orig_user['screen_name'];
                $tweet["iso_language_code"] = $value['metadata']->iso_language_code;
                $tweet["source"] = $value['source'];
                $tweet["profile_image_url"] = $value['user']->profile_background_image_url;
                $tweet["created_at"] = $value['created_at'];
              
                // extract geo information
                if ($value['geo'] != NULL)
                {
                    $geo = get_object_vars($value['geo']);
                    $geo_type = $geo['type'];
                    $geo_coordinates_0 = $geo['coordinates'][0];
                    $geo_coordinates_1 = $geo['coordinates'][1];
                } else
                {
                    $geo_type = NULL;
                    $geo_coordinates_0 = 0;
                    $geo_coordinates_1 = 0;
                }
                
                // duplicate record check and insert into proper cache table if not a duplicate
                $q_check = "select id from z_" . $row_archives['id'] . " where id = '" . $value['id'] . "'";
                $result_check = mysql_query($q_check, $db->connection);

                if (mysql_numrows($result_check) == 0)
                {
                    $num_inserted++;
                    $q = "insert into z_" . $row_archives['id'] . " values ('twitter-search','" . mysql_real_escape_string($tweet["text"]) . "','" . $tweet["to_user_id"] . "','" . $tweet["to_user"] . "','" . $tweet["from_user_id"] . "','" . $tweet["from_user"] . "','" . $tweet["original_user_id"] . "','" . $tweet["original_user"] . "','" . $tweet["id"] . "','" . $tweet["iso_language_code"] . "','" . $tweet["source"] . "','" . $tweet["profile_image_url"] . "','" . $geo_type . "','" . $geo_coordinates_0 . "','" . $geo_coordinates_1 . "','" . $tweet["created_at"] . "','" . strtotime($tweet["created_at"]) . "', NULL, NULL, NULL)";
                    mysql_query($q, $db->connection);
                    print "$q \n\n";
                    if (mysql_error() != "")
                        $tk->log("insert from crawling: " . mysql_error(), "", $crawl_log_file);

                    // add to update stream if tweet is not older than specified number of hours (cf. config).
                    // TODO [WARNING] This could possibly produce a too high amount of tweets to be able to update.
                    if (time() - $orig_time < $update_after)
                    {
                        mysql_query("insert into new_tweets values('" . $tweet["id"] . "', '" . $row_archives['id'] . "', '" . strtotime($tweet["created_at"]) . "', UNIX_TIMESTAMP(), '-1' )", $db->connection);
                        $tk->log("update required", "", $crawl_log_file);
                        
                    }
                    // track conversation if not too old and dealing with hashtagged tweet
                    //print "Type:" . $type . "  AND  " . (time() - $orig_time) . " < " . $time_to_track_user . "\n";                    
                    if (time() - $orig_time < $time_to_track_user && $type=="#")
                    {
                        $tk->trackConversation($row_archives['id'], $tweet);
                        $tk->log("conversation tracking required", "", $crawl_log_file);
                    }

                    $tk->log( "[" . $row_archives['id'] . "-" . $row_archives['keyword'] . "] $page_counter - " .  $tweet["id"] . " - insert\n", "", $crawl_log_file);
                } else
                {
                    //echo "[" . $row_archives['id'] . "-" . $row_archives['keyword'] . "] $page_counter - " .  $tweet["id"] . " - duplicate\n";
                }
                $max_id = $tweet["id"]; // resetting to lowest tweet id
            }

            // If count for page is less than 100, break since there is no reason to keep going
            if ($count < 100)
            {
                break;
            }

            //echo "\nmaxid = $max_id.\n";
        }

        // update counts
        $q_count_total = "select count(id) from z_" . $row_archives['id'];
        $r_count_total = mysql_query($q_count_total, $db->connection);
        $r_count_total = mysql_fetch_assoc($r_count_total);
        $q_update_count_total = "update archives set count = '" . $r_count_total['count(id)'] . "' where id = '" . $row_archives['id'] . "'";
        mysql_query($q_update_count_total, $db->connection);
        

        // update pid and last_ping in process table
        mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
        
        $tk->log("inserted " . $num_inserted, "", $crawl_log_file);
    }
}
?>
