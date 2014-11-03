<?php

// TODO decide upon adding tweets from users also to hashtag archives!
// load important files
require_once('config.php');
require_once('function.php');

// setup values
$pid = getmypid();
$script_key = uniqid();

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

// datastructure to save live stats
$total_count = array();

// process loop
while (TRUE)
{
    // check if all processes are still running
    $result = $tk->statusLiveArchiving();

    if (count($result[3]) > 0)
    {
        // notify admin and try to restart processes
        //mail($admin_mail_address, "Process(es) FAILED!", "Check TwapperKeeper!!!");
        /* $tk->log("Process(es) failed. Starting recovery modus!");

          foreach ($result[3] as $process)
          {
          $tk->log("Process " . $process . " failed. Restarting..");
          $res = mysql_fetch_assoc(mysql_query("select process, parameters from processes where pid = $process", $db->connection));
          $process_name = $res["process"];
          $params = $res["parameters"];
          $job = 'php ' . $tk_your_dir . $process_name . " " . $params;
          $tk->log($job);
          $pid = $tk->startProcess($job);
          mysql_query("update processes set pid = '$pid', live = '1' where process = '$process_name'", $db->connection);
          }

          // give processes time to start before new check
          sleep(2);

          // check
          $result = $tk->statusLiveArchiving();
          if (count($result[3]) > 0)
          {
          mail($admin_mail_address, "RESTART FAILED", "CHECK TwapperKeeper!!!");

          }
          else
          {
          mail($admin_mail_address, "Restart successful", "Check Logs of Twapperkeeper.");
          } */
    }



    // calculate statistics    
    $archives = array();
    $all_counts = array();
    $r = mysql_query("select type,keyword,count from archives where type IN (1,2,3,4)", $db->connection);
    while ($c = mysql_fetch_assoc($r))
    {
        $name = (($c["type"] == 2)? "#" : ($c["type"] == 3 || $c["type"] == 4)? "@" : "") . ucfirst($c["keyword"]);
        $all_counts[$name] = $c["count"];
        $archives[] = $name;
    }
    $total_count[] = $all_counts;
    if (count($total_count) >= 200)
    {
        $first = array_shift($total_count); 
        $last = end($total_count);

        $count_gain = array();
        foreach ($archives as $archive)
            $count_gain[$archive] = $last[$archive] - $first[$archive];

        arsort($count_gain);
        $active_archives = "";
        $i = 0;
        foreach ($count_gain as $a => $c)
        {
            $active_archives .= $a . "," . $c . ",";
            if ($i++ >= 5)
                break;
        }
        $active_archives = substr($active_archives, 0, -1);
    }
    else
        $active_archives = "";

    $r = mysql_query("select count(*) as total from smart_tweets", $db->connection);
    $total_num_tweets = mysql_fetch_assoc($r)["total"];
    mysql_free_result($r);

    $r = mysql_query("select count(*) as total from new_tweets where UNIX_TIMESTAMP() - fetched_at <= 3600", $db->connection);
    $num_tweets_last_hour = mysql_fetch_assoc($r)["total"];
    mysql_free_result($r);

    $r = mysql_query("select count(*) as total from new_tweets where UNIX_TIMESTAMP() - fetched_at <= 600", $db->connection);
    $num_tweets_last_10minutes = mysql_fetch_assoc($r)["total"];
    $avg_tweets_per_minute = round($num_tweets_last_hour / 60.0, 2);
    mysql_free_result($r);


    $r = mysql_query("select ROUND(SUM(track)/(COUNT(*)*$twitter_keyword_limit_per_stream) * 100, 1) as _load from users", $db->connection);
    $track_load = mysql_fetch_assoc($r)["_load"];
    mysql_free_result($r);

    $r = mysql_query("select ROUND(SUM(follow)/(COUNT(*)*$twitter_follow_limit_per_stream) * 100, 1) as _load from users", $db->connection);
    $follow_load = mysql_fetch_assoc($r)["_load"];
    mysql_free_result($r);

    $r = mysql_query("select COUNT(*) as count from archives where type = 2", $db->connection);
    $num_hashtags = mysql_fetch_assoc($r)["count"];
    mysql_free_result($r);

    $r = mysql_query("select COUNT(*) as count from archives where type = 3", $db->connection);
    $num_follows = mysql_fetch_assoc($r)["count"];
    mysql_free_result($r);

    $r = mysql_query("select COUNT(*) as count from archives where type = 4", $db->connection);
    $num_conversations = mysql_fetch_assoc($r)["count"];
    mysql_free_result($r);

    $r = mysql_query("select COUNT(*) as count from archives where type = 1", $db->connection);
    $num_keywords = mysql_fetch_assoc($r)["count"];
    mysql_free_result($r);

    /* if ($num_tweets_last_10minutes == 0)
      {
      mail($admin_mail_address, "No tweets fetched last 10 minutes!", "Check TwapperKeeper!!!");
      } */


    // Check if it should be updated or inserted
    $r = mysql_query("select created_at, id from statistics ORDER BY id DESC LIMIT 1");
    $s = mysql_fetch_assoc($r);
    $time = $s["created_at"];
    $id = $s["id"];
    mysql_free_result($r);

    if ((time() - $time) > 3600)
        mysql_query("insert into statistics values (0, '$total_num_tweets', $avg_tweets_per_minute, $track_load, $follow_load, '$num_hashtags', '$num_follows', '$num_keywords', '$num_conversations', '', UNIX_TIMESTAMP())", $db->connection);
    else
        mysql_query("update statistics set num_tweets = '$total_num_tweets', avg_tweets =$avg_tweets_per_minute, track_load=$track_load, follow_load=$follow_load, num_keywords='$num_keywords', num_hashtags='$num_hashtags', num_follows='$num_follows', num_conversations='$num_conversations', active_archives='$active_archives' where id='$id'", $db->connection);

    // sleep x seconds
    sleep($update_live_stats);
}
?>
