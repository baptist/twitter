<?php

// TODO Test what happens (if sleep works properly) when rate limit is hit.

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

$log = 'log/lookup_log';

// setup values
$pid = getmypid();
$sleep = 0;
$count = 0;
$count_limit = 100;
$rate_count = 0;
$rate_limit = 60;

$start_time = microtime();
$reset_time = 15 * 60; // 15 minutes
// Setup connection
$connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $tk_oauth_token, $tk_oauth_token_secret);
$connection->useragent = $youtwapperkeeper_useragent;

while (TRUE)
{
// Query for archives 
    $q_users = "select * from twitter_users where flag = 0";
    $r_users = mysql_query($q_users, $db->connection);

// Stop script if all users are checked
    if (($num = mysql_num_rows($r_users)) == 0)
        break;

    $screen_names = array();
    while ($row_users = mysql_fetch_assoc($r_users)) {

        $screen_names[] = trim($row_users['screen_name']);

        if ($count == $count_limit - 1 || $count >= ($num - 1)) {
            performLookup($screen_names);

            $rate_count++;

            if ($rate_count >= $rate_limit && ($start_time - microtime()) < $reset_time) {
                // sleep for rate limiting
                $tk->log("Sleep = " . ($reset_time - ($start_time - microtime())), "", $log);               
                sleep(($reset_time - ($start_time - microtime())));

                $start_time = microtime();
            }

            $count = 0;
            $screen_names = array();
        }
        $count++;
    }
    mysql_free_result($r_users);
}

function performLookup($screen_names) {
    global $connection;
    global $db;
    global $tk;
    global $log;        

    $users_found = array();
       

    $search = $connection->get('users/lookup', array('screen_name' => implode(",", $screen_names)));
    // check if search worked
    if (key_exists("errors", $search)) {
        // TODO check if rate limit error occurred
        $tk->log("Error occurred.", '', $log);
        $error = get_object_vars($search);
        
        var_dump($error);
        
        if (get_object_vars($error["errors"][0])["code"] == 88) {
            $status = $connection->get('application/rate_limit_status', array('resources' => "users"));
            $status_result = get_object_vars($status);
            $sleep = $status_result["resources"]["users"]["users/lookup"]["reset"] - microtime();
            $tk->log("Rate limit exceeded. Sleep = " . $sleep, "", $log);
            sleep($sleep);
        }
    } else {
        foreach ($search as $userobj) {
            $user = get_object_vars($userobj);
            
            $users_found[strtolower($user["screen_name"])] = 1;
            $q = "update twitter_users set twitter_id = '" . $user["id"] . "', full_name = \"" . $user["name"] . "\", followers = " . $user["followers_count"] . " , statuses = " . $user["statuses_count"] . ", json_object = '" . str_replace("'", "\'", utf8_encode(json_encode($user))) . "', flag = 1 where screen_name = '" . $user["screen_name"] . "'";
            mysql_query($q, $db->connection);           
        }
       
        foreach ($screen_names as $name) 
        {
            // TODO rollback dummy creation of archive when user does not exist.                        
            if (!array_key_exists(strtolower($name), $users_found))
                mysql_query("update twitter_users set flag = -1 where screen_name = '" . $name . "'", $db->connection);
        }
    }
}
?>
