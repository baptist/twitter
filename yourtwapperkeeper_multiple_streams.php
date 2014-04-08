<?php

require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');

// Check number of keywords to track and subdivide per user to meet Twitter limitations.
$q = "select COUNT(*) c from archives";
$num_archives = mysql_fetch_assoc(mysql_query($q, $db->connection))["c"];
print "ARCHIVES: " . $num_archives . "\n";
// Check number of users
$q = "select id from users";
$r_users = mysql_query($q, $db->connection);
$num_users = mysql_num_rows($r_users);

// Validity check
if ($num_users > $max_user_streams)
    $k->log("[NOTICE] Application will only use allowed number of users to perform streaming operations.");

$users = array();
$user_ballast = array();
while (count($users) < $max_user_streams) 
{
    $users[] = mysql_fetch_assoc($r_users);  
    $user_ballast[] = 0;
}
// Check if number of keywords is not too high
$streams_necessary = ceil( floatval($num_archives) / $twitter_keyword_limit_per_stream);
if ($streams_necessary > $max_user_streams)
{
    $streams_necessary = $max_user_streams;
    $k->log("[ERROR] Not all keywords will be tracked due to Twitter restrictions!");    
}
    
$user_index = 0;
mysql_query("UPDATE archives SET tracked_by = -1", $db->connection);


while ($user_index < $streams_necessary) 
{
    // Select number of archives that will be tracked by this stream
    mysql_query("UPDATE archives SET tracked_by = " . $users[$user_index]["id"] . " WHERE tracked_by = -1 LIMIT " .  $twitter_keyword_limit_per_stream, $db->connection);
    
    // Update stream information
    mysql_query("UPDATE users SET track = " . mysql_affected_rows() . " WHERE id = " . $users[$user_index]["id"], $db->connection);
      
    $job = 'php ' . $tk_your_dir . "yourtwapperkeeper_smart_stream.php ". $users[$user_index]["id"];
    $pid = $tk->startProcess($job);    
    mysql_query("update processes set pid = '$pid', live = 1 where process = 'yourtwapperkeeper_smart_stream_$user_index.php'", $db->connection);    
    
    $user_index++;
}

