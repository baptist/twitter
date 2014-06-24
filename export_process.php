<?php

set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*
ob_implicit_flush(true);
ob_end_flush();

header( 'Content-type: text/html; charset=utf-8' );

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
    $array = $_POST['keywords'];
    $keywords = "";
    foreach ($array as $selected)
        $keywords .= "'" . strtolower($selected) . "',";

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
    $tweets = $tk->getTweetsFromArchives($archives['results'], $from, $to, $limit, false, $no_rt, $no_mentions, false, false, false, false, false, false, false, false, false, $rt, $fv);


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
                    $stats[$key]['name'] = $user['full_name'];
                    $stats[$key]['followers'] = $user['followers'];
                    $stats[$key]['num_tweets_sent'] = 0;
                    $stats[$key]['num_retweets_sent'] = 0;
                    $stats[$key]['num_replies_sent'] = 0;
                    $stats[$key]['num_retweets_rec'] = 0;
                    $stats[$key]['num_replies_rec'] = 0;
                    $stats[$key]['num_favorites_rec'] = 0;
                }

                foreach ($tweets as $tweet)
                {
                    $key = strtolower($tweet['from_user']);
                    if (array_key_exists($key, $stats))
                    { // tweet from user
                        $stats[$key]['num_tweets_sent']++;

                        if (strpos(trim($tweet['text']), '@') === 0)
                            $stats[$key]['num_replies_sent']++;
                        else if (($tweet['original_user'] !== '' && $tweet['original_user'] != NULL) ||
                                (strpos($tweet['text'], 'RT @') === 0 && strtolower($tweet['original_user']) !== $key))
                            $stats[$key]['num_retweets_sent']++;

                        $stats[$key]['num_favorites_rec'] += ($tweet['favorites'] >= 0) ? $tweet['favorites'] : 0;
                        $stats[$key]['num_retweets_rec'] += ($tweet['retweets'] >= 0) ? $tweet['retweets'] : 0;
                    } else if (array_key_exists(strtolower($tweet['to_user']), $stats))
                    {
                        $stats[strtolower($tweet['to_user'])]['num_replies_rec']++;
                    }
                }
            }
        }
        $_SESSION['stats'] = $stats;
    }
    else
        $_SESSION['tweets'] = $tweets;
}
?>
