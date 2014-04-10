<?php

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth_search.php');

// setup values
$pid = getmypid();
$script_key = uniqid();

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

// process loop
// TODO limit updating to max possible amount
// TODO log whenever too many tweets enter so not all tweets can be updated in time.
// TODO Do not check retweets and favorites of tweet that was retweeted and already updated?
while (TRUE) {

    $start = microtime(true);
    // lock up tweets to update
    $q = "UPDATE new_tweets SET flag = '$script_key' WHERE flag = '-1' AND (UNIX_TIMESTAMP() - `created_at`) > $update_after limit $update_stack_size";
    mysql_query($q, $db->connection);

    $num_tweets = mysql_affected_rows();

    $tk->log("Started updating " . $num_tweets . " tweets. \n");

    if ($num_tweets > 0) 
    {
        // run java application
        $command = "java -jar library/updateTweets.jar '$script_key'";
        echo "$command \n";
        exec($command, $op);

        $time_run = (microtime(true) - $start);
        $tk->log("Application ran for $time_run seconds.\n");

        // delete tweets from update table 'new_tweets'
        mysql_query("DELETE FROM `new_tweets` WHERE flag = '$script_key'", $db->connection);
    }

    $time_left = $update_time_window - $time_run;
    $tk->log("Updating finished. Sleeping for $time_left seconds. \n");
    sleep($time_left);

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
    //echo "update pid\n";    
}
?>