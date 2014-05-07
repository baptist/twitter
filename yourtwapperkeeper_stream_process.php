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

// process loop
while (TRUE) {
    // lock up some tweets
    $q = "update rawstream set flag = '$script_key' where flag = '-1' limit $stream_process_stack_size";
    echo $q . "\n";
    mysql_query($q, $db->connection);

    $tk->log('Marking ' . mysql_affected_rows() . ' tweets.', '', $process_log_file);

    // get keyword into memory
    $q = "select id,keyword,track_id,type from archives";
    echo $q . "\n";
    $r = mysql_query($q, $db->connection);
    if (mysql_error() != "")
	$tk->log("Error when selecting archives: " . mysql_error(), '', $process_log_file);
    
    $tk->log("Num archives fetched: " . mysql_num_rows($r), '', $process_log_file);

    $track = array();
    $follow = array();
        
    // TODO decide which has priority over the other (user > hashtag > keyword ?)
    while ($row = mysql_fetch_assoc($r)) {

	//$tk->log('Looping over archives.', '', $process_log_file);

        if ($row["type"] == 1)
            $track[$row['id']] = $row['keyword'];
        else if ($row["type"] == 2)
            $track[$row['id']] = "#" . $row['keyword'];
        else if ($row["type"] == 3)
        {
            $follow[$row['id']] = $row['keyword'];
            $track[$row['id']] = "@" . $row['keyword'];
        }  
        else if ($row["type"] == 4)
            $follow[$row['id']] = $row['keyword'];
    }
    $tk->log("Loaded archives " . count($track) . " tracks and " . count($follow) . " follows.", '', $process_log_file);

    // grab the locked up tweets and load into memory
    $q = "select * from rawstream where flag = '$script_key'";
    $r = mysql_query($q, $db->connection);
    if (mysql_error() != "")
	$tk->log("Error when selecting tweets: " . mysql_error(), '', $process_log_file);
    
    echo $q . "\n";
    $batch = array();
    while ($row = mysql_fetch_assoc($r)) {
        $batch[] = $row;
    }
    $tk->log('Processing ' . mysql_num_rows($r) . ' tweets. Check (' . count($batch)  . ')', '', $process_log_file);


    // for each tweet in memory, compare against predicates and insert
    foreach ($batch as $tweet) 
    {
        $tk->log( "Processing [" . $tweet['id'] . " - " . $tweet['text'] . "]", '', $process_log_file);
        $inserted = FALSE;
        foreach ($follow as $ztable => $user) 
        {
            if (strcasecmp($user, $tweet['from_user'])  == 0)            
                $inserted = insert($ztable, $tweet, "creator");               
            
            else if (strcasecmp($user, $tweet['to_user']) == 0)            
                $inserted = insert($ztable, $tweet, "reply");                
            
            else if (strcasecmp($user, $tweet['original_user'])  == 0)            
                $inserted = insert($ztable, $tweet, "retweet");                
            
            else if (strcasecmp($user, substr(explode(" ", $tweet['text'])[0], 1))  == 0)            
                $inserted = insert($ztable, $tweet, "mention");           
        }

       
        $found = FALSE;
        foreach ($track as $ztable => $keyword) {

            if (stristr(strtolower($tweet['text']), strtolower($keyword)) == TRUE) {
                echo " vs. $keyword = insert\n";
                
                insert($ztable, $tweet, "keyword");

                // Check if keyword represents hashtag and start following user to record conversations if necessary.
                if ($keyword[0] == "#")
                    $tk->trackConversation($ztable, $tweet);   

                $found = TRUE;
            } else {
                //echo " vs. $keyword = not found\n";
            }
        }
        
        
        // If not found do not delete
        if (!$found && !$inserted)
	{            
            mysql_query("update rawstream set flag = '-2' where id = '" . $tweet['id'] . "'", $db->connection);
            $tk->log("Tweet could not be inserted.", '', $process_log_file);
	}
        echo "---------------\n";
    }
    // TODO find error that sets archives undefined
    // check if num of archives to track or follow differs from zero
    if (count($track) != 0 || count($follow) != 0)
    {
	// delete tweets in flag
	$q = "delete from rawstream where flag = '$script_key'";
	//echo $q . "\n";
	mysql_query($q, $db->connection);
    }
    else
    {
	mysql_query("update rawstream set flag = '-1' where flag = '$script_key'");
	$tk->log("No track or follow archives found!", '', $process_log_file);
	sleep(5);
    }

    // update counts

    foreach ($follow as $ztable => $keyword) {
        $q_count = "select count(id) from z_$ztable";
        $r_count = mysql_query($q_count, $db->connection) or die(mysql_error());
        $r_count = mysql_fetch_assoc($r_count);
        $q_update = "update archives set count = '" . $r_count['count(id)'] . "' where id = '$ztable'";
        //echo $q_update . "\n";
        mysql_query($q_update, $db->connection);
    }
    
    foreach ($track as $ztable => $keyword) {
        $q_count = "select count(id) from z_$ztable";
        $r_count = mysql_query($q_count, $db->connection) or die(mysql_error());
        $r_count = mysql_fetch_assoc($r_count);
        $q_update = "update archives set count = '" . $r_count['count(id)'] . "' where id = '$ztable'";
        //echo $q_update . "\n";
        mysql_query($q_update, $db->connection);
    }      

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
    //echo "update pid\n";

   // sleep to prevent error?
   sleep(2);
}

function insert($table_id, $tweet, $reason = "") {
    global $db;
    global $update_after;
    global $tk;
    global $process_log_file;
    
    // TODO log mysql errors
    $tk->log('Inserting tweet', '', $process_log_file);
    $q_insert = "insert into z_$table_id values ('twitter-stream-$reason','" . $tk->sanitize($tweet['text']) . "','" . ((string)$tweet['to_user_id']) . "','" . $tweet['to_user'] . "','" . ((string)$tweet['from_user_id']) . "','" . $tweet['from_user'] . "','" . ((string)$tweet['original_user_id']) . "','" . $tweet['original_user'] . "','" . ((string)$tweet['id']). "','" . ((string)$tweet['in_reply_to_status_id']) . "','" . $tweet['iso_language_code'] . "','" . $tweet['source'] . "','" . $tweet['profile_image_url'] . "','" . $tweet['geo_type'] . "','" . $tweet['geo_coordinates_0'] . "','" . $tweet['geo_coordinates_1'] . "','" . $tweet['created_at'] . "','" . $tweet['time'] . "', NULL, NULL, NULL)";
    $r_insert = mysql_query($q_insert, $db->connection);

    $tk->log("$q_insert", '', $process_log_file);
    if (mysql_error() != "")
	$tk->log("Error when inserting into archive $table_id" . mysql_error(), '', $process_log_file);

    if ($tweet['original_time'] > 0)
        $time = $tweet['original_time'];
    else
        $time = $tweet['time'];
    
    
    // Update is only required when tweet is not older than threshold
    $q2 = "insert into new_tweets values('".((string)$tweet['id'])."', $table_id, '". $time ."', UNIX_TIMESTAMP(), -1)";    
    $result = mysql_query($q2, $db->connection);        
    
    if (mysql_error() != '')
	$tk->log("Error when inserting into new tweets: " . mysql_error(), '', $process_log_file);
    
    return TRUE;
}





?>
