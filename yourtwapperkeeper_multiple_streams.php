<?php

require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');

$pid = getmypid();

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
    
    mysql_query("update processes set pid = '$pid', live = '1' where process = 'yourtwapperkeeper_smart_stream_$user_index.php'", $db->connection);    
    
    $user_index++;
}

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

while (TRUE)
{
    // sleep x second(s)
    sleep(3);
    
    // check if some users should not be followed anymore for conversation purposes
    $q_old_users = "update archives set type = 5, tracked_by = 0, followed_by = 0 where type = 4 AND id IN (select archive_id from conversations where (UNIX_TIMESTAMP() - `created_at`) > $time_to_track_user)";
    mysql_query($q_old_users, $db->connection);
    
    // assign floating archives to open and usable streams
    $q_floating_archives = "select id from archives where followed_by = '0' and type = '4'";
    $r = mysql_query($q_floating_archives, $db->connection);
    
    while($row = mysql_fetch_assoc($r))
    {
        // Pick a usable stream for user
        $r = mysql_query("SELECT id FROM users WHERE follow < $twitter_follow_limit_per_stream LIMIT 1");
        $num = mysql_num_rows($r);
        
        if ($num == 0)
        {            
            // Open new stream (if possible)
            if ($streams_necessary < $max_user_streams)
            {
                $job = 'php ' . $tk_your_dir . "yourtwapperkeeper_smart_stream.php ". $users[$user_index]["id"];
                $pid = $tk->startProcess($job);
                mysql_query("update processes set pid = '$pid', live = '1' where process = 'yourtwapperkeeper_smart_stream_$user_index.php'", $db->connection);    
    
                $user_index++;
                $streams_necessary++;
                
                $stream_id = $users[$user_index]["id"];
            } else
            {
                // TODO replace oldest followed user with this new user?
                $tk->log("[ERROR] Unable to follow user. No space left!");
                $stream_id = NULL;
            }             
        }
        else        
            $stream_id = mysql_fetch_assoc($r)["id"];
        
        if ($stream_id !== NULL)
        {
            mysql_query("update archives set followed_by = '$stream_id' where id = '".$row["id"]."'", $db->connection);
        }
        
    }
    
    
    for ($i = 0; $i < $streams_necessary; $i++)
    {
        // Get number of keywords that are tracked and followed by this stream
        $q_track = "select count(*) tracks from archives where tracked_by = '".$users[$i]["id"]."'";
        $num_tracks = mysql_fetch_assoc(mysql_query($q_track, $db->connection))["tracks"];
        $q_follow = "select count(*) follows from archives where followed_by = '".$users[$i]["id"]."'";
        $num_follows = mysql_fetch_assoc(mysql_query($q_follow, $db->connection))["follows"];
        // update follow and track counts
        $q_counts = "update users set follow = '$num_follows', track = '$num_tracks' where id = '".$users[$i]["id"]."'";
        mysql_query($q_counts, $db->connection);        
    }
    
    
}

?>