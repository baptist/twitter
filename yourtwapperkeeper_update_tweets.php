<?php

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth_search.php');

// setup values
$pid = getmypid();
$script_key = uniqid();
$log = 'log/update_log';

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

// process loop
// TODO limit updating to max possible amount
// TODO log whenever too many tweets enter so not all tweets can be updated in time.
while (TRUE) {

    $start = microtime(true);
    // lock up tweets to update
    $q = "UPDATE new_tweets SET flag = '$script_key' WHERE flag = '-1' AND (UNIX_TIMESTAMP() - `created_at`) > $update_after limit $update_stack_size";
    mysql_query($q, $db->connection);

    $num_tweets = mysql_affected_rows();

    $tk->log("Started updating " . $num_tweets . " tweets." , '', $log);

    if ($num_tweets > 0) 
    {
        // run java application
        $command = "java -jar library/updateTweets.jar '$script_key' >> $log 2>> $log";
        exec($command, $op);        
        foreach ($op as $line)        
             $tk->log($line , '', $log);        

        $time_run = (microtime(true) - $start);
        $tk->log("Application ran for $time_run seconds" , '', $log);

        // delete tweets from update table 'new_tweets'
        mysql_query("DELETE FROM `new_tweets` WHERE flag = '$script_key'", $db->connection);
    }

    $time_left = $update_time_window - $time_run;
    
    if ($time_left > 0)
    {
        $tk->log("Updating finished. Sleeping for $time_left seconds.", '', $log);  
        sleep($time_left);
    } else
        $tk->log("Application has run too long!", '', $log); 
       

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
}
?>