<?php

set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*
ob_implicit_flush(true);
ob_end_flush();

header('Content-type: text/html; charset=utf-8');

echo "<script type='text/javascript'>parent.displayExport();</script>";

include_once('config.php');
include_once('function.php');

$condition = "1";
if (!empty($_POST['tags']))
{
    $tags_array = $_POST['tags'];
    $tags = "";
    foreach ($tags_array as $selected)
        $tags .= strtolower($selected) . "|";

    $condition .= " and tags regexp '" . substr($tags, 0, -1) . "'";
}

if (!empty($_POST['type']))
{
    $type_array = $_POST['type'];
    $type = "";
    foreach ($type_array as $selected)
        $type .= strtolower($selected) . "|";

    $condition .= " and type regexp '" . substr($type, 0, -1) . "'";
}

if (!empty($_POST["keywords"]))
{
    $array = explode(",", $_POST['keywords']);
    $keywords = "";
    foreach ($array as $selected)
        $keywords .= "'" . strtolower(trim($selected)) . "',";

    /* $keywords = ""; 
      $f = fopen("export.csv", "r");
      while ($line = fgets ($f, 4096))
      $keywords .= "'" . strtolower(trim($line)) . "',"; */

    $condition .= " and keyword in (" . substr($keywords, 0, -1) . ")";
}

if (!empty($_POST["description"]))
    $condition .= " and description LIKE '" . $_POST["description"] . "'";

$limit = false;
if (!empty($_POST["limit"]))
    $limit = $_POST["limit"];

$no_mentions = false;
if (!empty($_POST["no_mentions"]))
    $no_mentions = $_POST["no_mentions"];

$rt = false;
if (!empty($_POST["rt"]))
    $rt = $_POST["rt"];

$fv = false;
if (!empty($_POST["fv"]))
    $fv = $_POST["fv"];

$no_rt = false;
if (!empty($_POST["no_rt"]))
    $no_rt = $_POST["no_rt"];

$include_reactions = false;
if (!empty($_POST["include_reactions"]))
    $include_reactions = $_POST["include_reactions"];

$from = false;
if (!empty($_POST["from"]))
    $from = DateTime::createFromFormat('d/m/Y H:i:s', $_POST["from"] . " 00:00:00")->getTimestamp();

$to = false;
if (!empty($_POST["to"]))
    $to = DateTime::createFromFormat('d/m/Y H:i:s', $_POST["to"] . " 23:59:59")->getTimestamp();

$archives = $tk->listArchivesWithCondition("$condition ORDER BY count DESC");

if ($archives['count'] === 0)
    $_SESSION['tweets'] = array();
else
{
    $tweets = $tk->getTweetsFromArchives($archives['results'], $from, $to, $limit, false, $no_rt, $no_mentions, false, false, false, false, false, false, false, false, false, $rt, $fv, $include_reactions);


    $groupings = false;
    $process = false;
    if (!empty($_POST["groupings"]))
    {
        $stats = array();

        $user_stats = false;
        if (!empty($_POST["user_stats"]))
            $user_stats = $_POST["user_stats"];

        $tweet_stats = false;
        if (!empty($_POST["tweets_stats"]))
            $tweet_stats = $_POST["tweets_stats"];

        $process = true;

        foreach ($_POST["groupings"] as $grouping)
        {
            if (strcasecmp($grouping, "user") === 0)
            {
                foreach ($archives['results'] as $archive)
                {
                    $key = strtolower($archive['keyword']);
                    $stats[$key] = array();
                    $user = $tk->getUser($key);
                    $stats[$key]['screen_name'] = $key;
                    $stats[$key]['name'] = $user['full_name'];
                    $stats[$key]['followers'] = $user['followers'];
                    $stats[$key]['num_tweets_sent'] = 0;
                    $stats[$key]['num_retweets_sent'] = 0;
                    $stats[$key]['num_replies_sent'] = 0;
                    $stats[$key]['num_mentions_sent'] = 0;
                    $stats[$key]['num_retweets_rec'] = 0;
                    $stats[$key]['num_favorites_rec'] = 0;
                    $stats[$key]['num_replies_rec'] = 0;
                    $stats[$key]['num_mentions_rec'] = 0;
                }

                foreach ($tweets as $tweet)
                {
                    $is_retweet = false;

                    $key = strtolower($tweet['from_user']);
                    if (array_key_exists($key, $stats))
                    { // tweet from user
                        $stats[$key]['num_tweets_sent']++;

                        if (strpos(trim($tweet['text']), '@') === 0)
                            $stats[$key]['num_replies_sent']++;
                        else if (($tweet['original_user'] !== '' && $tweet['original_user'] != NULL) ||
                                (strpos($tweet['text'], 'RT @') === 0 && strtolower($tweet['original_user']) !== $key))
                        {
                            $is_retweet = true;
                            $stats[$key]['num_retweets_sent']++;
                        } else if (strpos(trim($tweet['text']), '@') > 0)
                            $stats[$key]['num_mentions_sent']++;


                        if (!$is_retweet)
                        {
                            $stats[$key]['num_favorites_rec'] += ($tweet['favorites'] >= 0) ? $tweet['favorites'] : 0;
                            $stats[$key]['num_retweets_rec'] += ($tweet['retweets'] >= 0) ? $tweet['retweets'] : 0;
                        }
                    } else if (array_key_exists(strtolower($tweet['to_user']), $stats))
                    {
                        $stats[strtolower($tweet['to_user'])]['num_replies_rec']++;
                    }
                    
                    // If tweet is no retweet check which users are mentioned.
                    if (!(($tweet['original_user'] !== '' && $tweet['original_user'] != NULL) ||
                            (strpos($tweet['text'], 'RT @') === 0 && strtolower($tweet['original_user']) !== $key)))
                    {
                        $mentioned = array();
                        $lastPos = 1;                        
                        while (($lastPos = strpos($tweet['text'], "@", $lastPos)) !== false)
                        {
                            $next_pos = strpos($tweet['text'], " ", $lastPos + 1);
                            $mentioned_name = ($next_pos !== false)? substr($tweet['text'], $lastPos + 1, $next_pos - ($lastPos + 1)) : substr($tweet['text'], $lastPos + 1);                            
                            $mentioned[] = $mentioned_name;
                            $lastPos = $next_pos;                            
                        }
                                                
                        foreach ($mentioned as $mention)
                        {
                            if (array_key_exists(strtolower($mention), $stats))
                                $stats[strtolower($mention)]['num_mentions_rec']++;
                        }
                    }
                }
            } else if (strcasecmp($grouping, "total") === 0)
            {
                $users = array();
                $stats['total'] = array();
                $stats['total']['type'] = 'total';
                $stats['total']['num_tweets'] = 0;
                foreach ($tweets as $tweet)
                {
                    $stats['total'] ['num_tweets']++;
                    $users[$tweet['from_user']] = 1;
                }
                $stats['total']['num_users'] = count(array_keys($users));
            } else
            {
                foreach ($tweets as $tweet)
                {
                    $user = $tweet['from_user'];

                    switch ($grouping)
                    {
                        case "year":
                            $timing = mktime(0, 0, 0, 0, 0, date('Y', $tweet['time']));
                            $formatted_timing = date('Y', $tweet['time']);
                            break;
                        case "month":
                            $timing = mktime(0, 0, 0, date('m', $tweet['time']), 0, date('Y', $tweet['time']));
                            $formatted_timing = date('m-Y', $tweet['time']);
                            break;
                        case "day":
                            $timing = mktime(0, 0, 0, date('n', $tweet['time']), date('j', $tweet['time']), date('Y', $tweet['time']));
                            $formatted_timing = date('d-m-Y', $tweet['time']);
                            break;
                        case "hour":
                            $timing = mktime(date('H', $tweet['time']), 0, 0, date('n', $tweet['time']), date('j', $tweet['time']), date('Y', $tweet['time']));
                            $formatted_timing = date('H:00 d-m-Y', $tweet['time']);
                            break;
                    }

                    if (!isset($stats[$timing]))
                    {
                        $stats[$timing] = array();
                        $stats[$timing]['type'] = $formatted_timing;
                        $stats[$timing]['num_tweets'] = 0;
                        $stats[$timing]['users'] = array();
                    }

                    $stats[$timing]['num_tweets']++;
                    $stats[$timing]['users'][$tweet['from_user']] = 1;
                }

                foreach ($stats as $key => $value)
                {
                    if (array_key_exists("users", $stats[$key]))
                    {
                        $stats[$key]['num_users'] = count($stats[$key]['users']);
                        unset($stats[$key]['users']);
                    }
                }
                var_dump($stats);
                ksort($stats);
            }
        }
        $tk->saveExport($stats);
    } else
    {
        $keys = array("text", "from_user_id", "from_user", "to_user_id", "to_user", "original_user_id",
            "original_user", "id", /* "in_reply_to_status_id", */ "iso_language_code", "profile_image_url",
            "geo_type", "geo_coordinates_0", "geo_coordinates_1", "created_at", "time", "favorites", "retweets", "description", "tags");
        $data = array();
        foreach ($tweets as $tweet)
        {
            $data[$tweet['id']] = array();
            foreach ($keys as $key)
                $data[$tweet['id']][$key] = $tweet[$key];
        }
        $tk->saveExport($data);
    }

    $_SESSION['export_from_table'] = 1;

    echo "<script type='text/javascript'>parent.setInformation('" . count($tweets) . "','" . $archives['count'] . "');</script>";
}
?>
