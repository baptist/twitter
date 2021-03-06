<?php

require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');

$log = "log/multiple_stream_log";
$pid = getmypid();

// Update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

if ($application_reset)
{
    // Reset streams counters
    mysql_query("UPDATE users SET track = 0, follow = 0", $db->connection);

    // Reset tracking and follow information for archives
    mysql_query("UPDATE archives SET tracked_by = 0, followed_by = 0", $db->connection);
}

// Check number of streams
$q = "select id from users";
$r_streams = mysql_query($q, $db->connection);
$num_streams = mysql_num_rows($r_streams);

// Validity check
if ($num_streams > $max_user_streams)
{
    $k->log("[NOTICE] Application will only use allowed number of users to perform streaming operations.", '', $log);
    $num_streams = $max_user_streams;
}
// Get all streams
$streams = array();
$streams_live = array();
while (count($streams) < $num_streams)
{
    $u = mysql_fetch_assoc($r_streams);
    $streams[] = $u;
    //$live = mysql_fetch_num(mysql_query("select pid from processes where live = '1' and parameters = '".$u["id"]."'", $db->connection)) > 0;
    $streams_live[$u["id"]] = 0; //$live;
}

// Start looping
$count = 0;
$last_updated = 0;
// TODO make this more efficient as a whole.
while (TRUE)
{
    $streams_shouldbe_live = array();

    // Check if some users should not be followed anymore for conversation purposes (only every 30 minutes!)
    if (time() - $last_updated >= 1800)
    {
        $subquery = "select archive from conversations WHERE (UNIX_TIMESTAMP() - `created_at`) > $time_to_track_user";
        $archives_to_drop_temp = mysql_query($subquery, $db->connection);
        $tk->log(mysql_error($db->connection), 'mysql-select-archives', $log);

        if (mysql_num_rows($archives_to_drop_temp) > 0)
        {
            $archives = '';
            while ($r = mysql_fetch_assoc($archives_to_drop_temp))
                $archives .= "," . $r['archive'];

            $tk->untrackConversations(substr($archives, 1));
        }
        
        mysql_free_result($archives_to_drop_temp);
        $last_updated = time();
    }

    // Update stream track and follow statistics
    for ($i = 0; $i < count($streams); $i++)
    {
        // Get number of keywords that are tracked and followed by this stream
        $q_track = "select count(*) tracks from archives where tracked_by = '" . $streams[$i]["id"] . "'";
        $num_tracks = mysql_fetch_assoc(mysql_query($q_track, $db->connection))["tracks"];
        $q_follow = "select count(*) follows from archives where followed_by = '" . $streams[$i]["id"] . "'";
        $num_follows = mysql_fetch_assoc(mysql_query($q_follow, $db->connection))["follows"];

        // check if counts are zero or not
        if ($num_tracks > 0 || $num_follows > 0)
            $streams_shouldbe_live[$streams[$i]["id"]] = 1;

        // update follow and track counts
        $q_counts = "update users set follow = '$num_follows', track = '$num_tracks' where id = '" . $streams[$i]["id"] . "'";
        mysql_query($q_counts, $db->connection);
    }

    // Assign floating archives of type 3 and 4 to open and usable streams
    $q_floating_archives = "select id from archives where followed_by = '0' and type IN (3,4)";
    $r = mysql_query($q_floating_archives, $db->connection);
    $tk->log(mysql_error($db->connection), 'mysql-assign-3,4', $log);

    while ($row = mysql_fetch_assoc($r))
    {
        // Pick a usable stream for user
        $stream_id = getUsableStreamId(-1, $twitter_follow_limit_per_stream);
        if ($stream_id !== NULL)
        {
            mysql_query("update archives set followed_by = '$stream_id' where id = '" . $row["id"] . "'", $db->connection);
            mysql_query("update users set follow = follow + 1 where id = $stream_id", $db->connection);

            $streams_shouldbe_live[$stream_id] = 1;
        } else
        {
            // Remove conversation based on certain criteria
            switch ($track_remove_policy)
            {
                case "UNACTIVE" :
                    break;

                case "OLDEST":
                    $subr = mysql_query("select followed_by as stream_id, id from archives where id = (SELECT archive from conversations order by created_at ASC LIMIT 1)", $db->connection);
                    $result = mysql_fetch_assoc($subr);
                    mysql_free_result($subr);
                    $tk->log(mysql_error($db->connection), 'mysql-removepolicy-oldest', $log);

                    $stream_id = $result['stream_id'];
                    $tk->untrackConversation($result['id']);
                    mysql_query("update archives set followed_by = '$stream_id' where id = '" . $row["id"] . "'", $db->connection);

                    $streams_shouldbe_live[$stream_id] = 1;

                    break;

                default :
                    break;
            }
        }
    }    
    mysql_free_result($r);

    // assign floating archives of type 1,2,3 to open and usable streams
    $q_floating_archives = "select id from archives where tracked_by = '0' and type IN (1,2,3)";
    $r = mysql_query($q_floating_archives, $db->connection);
    $tk->log(mysql_error($db->connection), 'mysql-assign-1,2,3', $log);

    while ($row = mysql_fetch_assoc($r))
    {
        // Pick a usable stream for user
        $stream_id = getUsableStreamId($twitter_keyword_limit_per_stream, -1);
        if ($stream_id !== NULL)
        {
            mysql_query("update archives set tracked_by = '$stream_id' where id = '" . $row["id"] . "'", $db->connection);
            mysql_query("update users set track = track + 1 where id = $stream_id", $db->connection);

            $streams_shouldbe_live[$stream_id] = 1;
        }
    }
    mysql_free_result($r);

    // start or stop streams based on the necessity
    for ($i = 0; $i < count($streams); $i++)
    {
        if ((array_key_exists($streams[$i]["id"], $streams_shouldbe_live) && $streams_shouldbe_live[$streams[$i]["id"]]) && !$streams_live[$streams[$i]["id"]])
        {
            // start stream
            $job = 'php ' . $tk_your_dir . "yourtwapperkeeper_smart_stream.php " . $streams[$i]["id"];
            $pid = $tk->startProcess($job);
            mysql_query("update processes set pid = '$pid', live = '1', parameters = '" . $streams[$i]["id"] . "' where process = 'yourtwapperkeeper_smart_stream_$i.php'", $db->connection);
            $streams_live[$streams[$i]["id"]] = 1;
        } else if ((!array_key_exists($streams[$i]["id"], $streams_shouldbe_live) || !$streams_shouldbe_live[$streams[$i]["id"]]) && $streams_live[$streams[$i]["id"]])
        {
            // stop stream
            $subr = mysql_query("select pid from processes where process = 'yourtwapperkeeper_smart_stream_$i.php'", $db->connection);
            $tpid = mysql_fetch_assoc($subr);
            mysql_free_result($subr);
            $tk->killProcess($tpid['pid']);
            mysql_query("update processes set pid = '0', live = '0', parameters = '' where process = 'yourtwapperkeeper_smart_stream_$i.php'", $db->connection);
            $streams_live[$streams[$i]["id"]] = 0;
        }
    }

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);

    sleep(3);

    $count++;
}

function getUsableStreamId($track_limit, $follow_limit)
{
    global $tk;
    global $db;


    $r = mysql_query("SELECT id FROM users WHERE follow " . (($follow_limit > 0) ? "<" . $follow_limit : ">= 0") . " AND track " . (($track_limit > 0) ? "<" . $track_limit : ">= 0") . " LIMIT 1", $db->connection);
    $num = mysql_num_rows($r);

    if ($num == 0)
    {
        // TODO replace oldest followed user with this new user?
        $tk->log("[ERROR] Unable to follow or track user. No space left!", 'error', $log);
        $stream_id = NULL;
    }
    else
        $stream_id = mysql_fetch_assoc($r)["id"];
    
    mysql_free_result($r);

    return $stream_id;
}

?>