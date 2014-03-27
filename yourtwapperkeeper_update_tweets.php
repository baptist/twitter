<?php

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth_search.php');

// setup values
$pid = getmypid();
$script_key = uniqid();

// process loop

// TODO limit updating to max possible amount
// TODO log whenever too many tweets enter so not all tweets can be updated in time.
while (TRUE) {
    // lock up tweets to update
    $q = "UPDATE new_tweets SET flag = '$script_key' WHERE flag = '-1' AND (UNIX_TIMESTAMP() - `created_at`) > $update_after limit $update_stack_size";
    mysql_query($q, $db->connection);
    
       
  
    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
    //echo "update pid\n";
    
    
    //sleep($update_time_window);
}



?>
