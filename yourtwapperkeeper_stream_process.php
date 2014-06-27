<?php

// TODO decide upon adding tweets from users also to hashtag archives!
// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth_search.php');

// setup values
$pid = getmypid();
$script_key = uniqid();

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

// log file
$process_log_file = "log/process_log";
$process_error_log_file = "log/process_error_log";
$last_updated = 0;
$processed = 0;

// reset tweets
mysql_query("update rawstream set flag = -1", $db->connection);

while (TRUE)
{
    // Update follow and track keywords
    $time_passed_by = time() - $last_updated;
    if ($time_passed_by >= 0)
    {
        // get keyword into memory
        $q = "select id,LOWER(keyword) AS keyword,track_id,type from archives";
        $r = mysql_query($q, $db->connection);
        $tk->log(mysql_error($db->connection), 'mysql-select-archives', 'log/special');
        
        //echo "COUNTER VALUE: " . counter_get_value($r) . "\n";
        if (!is_resource($r))
        {            
            $tk->log("ERROR WHEN SELECTING ARCHIVES!", 'mysql-select-archives', 'log/special');
            $status_query = mysql_query("SHOW STATUS", $db->connection);
            while ($row = mysql_fetch_assoc($status_query))
                $tk->log($row['Variable_name'] . "\t=\t" . $row['Value'], '', 'log/special');
        }
        
        if (mysql_num_rows($r) != 0)
        {
            $track = array();
            $follow = array();

            $tk->log("Num archives fetched: " . mysql_num_rows($r), '', $process_log_file);

            while ($row = mysql_fetch_assoc($r))
            {
                if ($row["type"] == 1)
                    $track[$row['id']] = $row['keyword'];
                else if ($row["type"] == 2)
                    $track[$row['id']] = "#" . $row['keyword'];
                else if ($row["type"] == 3 || $row["type"] == 4)
                {
                    $follow[$row['keyword']] = $row['id'];
                    $track[$row['id']] = "@" . $row['keyword'];
                }
            }
            $tk->log("Loaded archives " . count($track) . " tracks and " . count($follow) . " follows.", '', $process_log_file);
        } else
        {
            $tk->log("Could not fetch old archives. Using old data to process tweets.", "", $process_error_log_file);
        }
      
        $tk->log('Processing ' . ($processed/(($time_passed_by == 0)? 1 : $time_passed_by )) . ' tweets per second. (Total:'.$processed.' in '.$time_passed_by.'s)', '', $process_log_file);

        $processed = 0;
        $last_updated = time();
        echo "(2) VALUE: " . intval($r) . "\n";
        mysql_free_result($r);
        unset($r);
        
        // update counts
        foreach ($follow as $keyword => $ztable)
        {
            $q_count = "select count(id) from z_$ztable";
            $r_count = mysql_query($q_count, $db->connection) or die(mysql_error());
            $obj = mysql_fetch_assoc($r_count);
            $q_update = "update archives set count = '" . $obj['count(id)'] . "' where id = '$ztable'";
            mysql_query($q_update, $db->connection);
            
            mysql_free_result($r_count);
            unset($r_count);
        }

        foreach ($track as $ztable => $keyword)
        {
            $q_count = "select count(id) from z_$ztable";
            $r_count = mysql_query($q_count, $db->connection) or die(mysql_error());            
            $obj = mysql_fetch_assoc($r_count);
            $q_update = "update archives set count = '" . $obj['count(id)'] . "' where id = '$ztable'";
            mysql_query($q_update, $db->connection);
            
            mysql_free_result($r_count);
            unset($r_count);
        }
    }


    // lock up some tweets
    $q = "update rawstream set flag = '$script_key' where flag = '-1' limit $stream_process_stack_size";
    mysql_query($q, $db->connection);

    // grab the locked up tweets and load into memory
    $q = "select * from rawstream where flag = '$script_key'";
    $r = mysql_query($q, $db->connection);
    $tk->log(mysql_error($db->connection), 'mysql-error-selecting-tweets', $process_error_log_file);

    $batch = array();
    while ($row = mysql_fetch_assoc($r))    
        $batch[] = $row;    
    $processed += mysql_num_rows($r);
    
    mysql_free_result($r);

    // for each tweet in memory, compare against predicates and insert
    foreach ($batch as $tweet)
    {
        if (isset($follow[strtolower($tweet['from_user'])]))
            $inserted = $tk->insertTweet($follow[strtolower($tweet['from_user'])], $tweet, -1, "stream-creator", $process_error_log_file);

        if (isset($follow[strtolower($tweet['to_user'])]))
            $inserted = $tk->insertTweet($follow[strtolower($tweet['to_user'])], $tweet, -1, "stream-reply", $process_error_log_file);

        if (isset($follow[strtolower($tweet['original_user'])]))
            $inserted = $tk->insertTweet($follow[strtolower($tweet['original_user'])], $tweet, -1, "stream-retweet", $process_error_log_file);

        foreach ($track as $ztable => $keyword)
        {
            if (stristr(strtolower($tweet['text']), strtolower($keyword)) == TRUE)
                $inserted = $tk->insertTweet($ztable, $tweet, ($keyword[0] == "#") ? 2 : -1, "stream-keyword", $process_error_log_file);
        }

        // If not found do not delete
        if (!$inserted)
        {
            mysql_query("insert into failed_tweets select * from rawstream where id = '" . $tweet['id'] . "'", $db->connection);
            $tk->log("Tweet could not be inserted.", '', $process_error_log_file);
        }
    }

    // check if num of archives to track or follow differs from zero
    if (count($track) != 0 || count($follow) != 0)
    {
        // delete tweets in flag
        $q = "delete from rawstream where flag = '$script_key'";
        mysql_query($q, $db->connection);
    } else
    {
        mysql_query("update rawstream set flag = '-1' where flag = '$script_key'");
        $tk->log("No track or follow archives found!", '', $process_error_log_file);
        sleep(5);
    }

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
}
?>
