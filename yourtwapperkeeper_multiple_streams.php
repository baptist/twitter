<?php

require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');

// Check number of keywords to track and subdivide per user to meet Twitter limitations.
$q = "select COUNT(*) c from archives where type in (1,2,3)";
$num_archives_track = mysql_fetch_assoc(mysql_query($q, $db->connection))["c"];
print "ARCHIVES TO TRACK: " . $num_archives_track . "\n";

// Check number of users to follow
$q = "select COUNT(*) c from archives where type in (3,4)";
$num_archives_follow = mysql_fetch_assoc(mysql_query($q, $db->connection))["c"];
print "ARCHIVES TO TRACK: " . $num_archives_follow . "\n";

// Check number of streams
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
$streams_necessary = max(ceil( floatval($num_archives_track) / $twitter_keyword_limit_per_stream), ceil( floatval($num_archives_follow) / $twitter_follow_limit_per_stream));
if ($streams_necessary > $max_user_streams)
{
    $streams_necessary = $max_user_streams;
    $k->log("[ERROR] Not all keywords will be tracked or followed due to Twitter restrictions!");    
}
    
$user_index = 0;

mysql_query("UPDATE archives SET tracked_by = -1, followed_by = 0 WHERE type IN (1,2)", $db->connection);
mysql_query("UPDATE archives SET tracked_by = -1, followed_by = -1 WHERE type = 3", $db->connection);
mysql_query("UPDATE archives SET tracked_by = 0, followed_by = -1 WHERE type = 4", $db->connection);

while ($user_index < $streams_necessary) 
{
    // Select number of archives that will be tracked by this stream
    mysql_query("UPDATE archives SET tracked_by = " . $users[$user_index]["id"] . " WHERE tracked_by = -1 AND type IN (1,2,3) LIMIT " .  $twitter_keyword_limit_per_stream, $db->connection);
    
    // Update stream information
    mysql_query("UPDATE users SET track = " . mysql_affected_rows() . " WHERE id = " . $users[$user_index]["id"], $db->connection);
          
    // Select number of archives that will be tracked by this stream
    mysql_query("UPDATE archives SET followed_by = " . $users[$user_index]["id"] . " WHERE followed_by = -1 AND type IN (3, 4) LIMIT " .  $twitter_follow_limit_per_stream, $db->connection);
    
    // Update stream information
    mysql_query("UPDATE users SET follow = " . mysql_affected_rows() . " WHERE id = " . $users[$user_index]["id"], $db->connection);
    
    $job = 'php ' . $tk_your_dir . "yourtwapperkeeper_smart_stream.php ". $users[$user_index]["id"];
    $pid = $tk->startProcess($job);
    
    $user_index++;
}

?>