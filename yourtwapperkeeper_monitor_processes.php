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

// process loop
while (TRUE)
{
    // check if all processes are still running
    $result = $tk->statusLiveArchiving();
        
    if (count($result[3]) > 0)
    {
        // notify admin and try to restart processes
        mail("baptist.vandersmissen@ugent.be", "Process(es) failed! Trying to restart.", "Check TwapperKeeper!");

                
    }


    // sleep x seconds
    sleep(5);
}
?>
