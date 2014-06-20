<?php

require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');


date_default_timezone_set("Europe/Brussels");

if (isset($argv[1]))
    $stream_id = $argv[1];

if ($stream_id == NULL)
{
    $tk->log("[ERROR] No ID given to track keywords with.", 'error', 'log/main_error_log');
    exit(2);
}

// Get user information
$subr = mysql_query("SELECT * FROM users WHERE id = " . $stream_id, $db->connection);
$user = mysql_fetch_assoc($subr);
mysql_free_result($user);

define('TWITTER_CONSUMER_KEY', $user["consumer_key"]);
define('TWITTER_CONSUMER_SECRET', $user["consumer_secret"]);

class DynamicTrackConsumer extends OauthPhirehose {

    public function enqueueStatus($status)
    {
        global $db;
        global $tk;
        global $stream_id;

        $status = json_decode($status);
        $status = get_object_vars($status);

        // TODO check if error message is sent back!
        if ($status['id'] <> null)
        {

            $values_array = array();

            if (isset($status['geo']))
                $geo = get_object_vars($status['geo']);
            else
            {
                $geo = array();
                $geo['type'] = '';
                $geo['coordinates'] = array();
                $geo['coordinates'][0] = '';
                $geo['coordinates'][1] = '';
            }
            
            $user = get_object_vars($status['user']);
            $in_reply_to_status_id = (string) (array_key_exists('in_reply_to_status_id_str', $status)) ? $status['in_reply_to_status_id_str'] : "";

            if (array_key_exists('retweeted_status', $status))
            {
                $orig = get_object_vars($status['retweeted_status']);
                $orig_user = get_object_vars($orig["user"]);
                $orig_time = strtotime($orig["created_at"]);

                $text = "RT @" . $orig_user['screen_name'] . ": " . $tk->sanitize($orig['text']);
                $orig_id = $orig["id"];
            } else
            {
                $orig_user["id"] = "";
                $orig_user["screen_name"] = "";
                $orig_time = 0;
                $orig_id = 0;
                $text = $tk->sanitize($status['text']);
            }

            $values_array[] = "-1";                                     // processed_flag [-1 = waiting to be processed]
            $values_array[] = $text;                                    // text
            $values_array[] = (string) $status['in_reply_to_user_id'];  // to_user_id
            $values_array[] = $status['in_reply_to_screen_name'];       // to_user
            $values_array[] = (string) $user['id'];                     // from_user_id
            $values_array[] = $user['screen_name'];                     // from_user 
            $values_array[] = (string) $orig_user['id'];                // original_user_id
            $values_array[] = $orig_user['screen_name'];                // original_user          
            $values_array[] = (string) $status['id'];                   // id -> unique id of tweet  
            $values_array[] = $orig_id;                                 // original id
            $values_array[] = $in_reply_to_status_id;                   // in reply to status id
            $values_array[] = $user['lang'];                            // iso_language_code
            $values_array[] = $status['source'];                        // source
            $values_array[] = $user['profile_image_url'];               // profile_img_url
            $values_array[] = $geo['type'];                             // geo_type 
            $values_array[] = $geo['coordinates'][0];                   // geo_coordinates_0
            $values_array[] = $geo['coordinates'][1];                   // geo_coordinates_1
            $values_array[] = $status['created_at'];                    // created_at
            $values_array[] = strtotime($status['created_at']);         // time            
            $values_array[] = $orig_time;                               // original time

            $values = '';
            foreach ($values_array as $insert_value)
            {
                $values .= "'$insert_value',";
            }
            $values = substr($values, 0, -1);

            // add to list of newly created tweets.
            $q1 = "insert into rawstream values($values)";
            $result = mysql_query($q1, $db->connection);
            $tk->log(mysql_error($db->connection), 'mysql-enqueueStatus-insert', "log/stream_" . $stream_id . "_log");
        }
    }

    public function checkFilterPredicates()
    {
        global $db;
        global $stream_id;
        global $tk;

        $q = "select id,keyword,type,track_id,tracked_by,followed_by from archives where tracked_by =  '$stream_id' OR followed_by = '$stream_id'";
        $r = mysql_query($q, $db->connection);
        $tk->log(mysql_error($db->connection), 'mysql-checkFilterPredicates-selectarchives', "log/stream_" . $stream_id . "_log");               

        $track = array();
        $follow = array();
        while ($row = mysql_fetch_assoc($r))
        {

            if ($row["tracked_by"] == $stream_id)
            {
                if ($row["type"] == 1)
                    $track[] = $row['keyword'];
                else if ($row["type"] == 2)
                    $track[] = "#" . $row['keyword'];
                else if ($row["type"] == 3)
                    $track[] = "@" . $row['keyword'];
            }

            if ($row["followed_by"] == $stream_id)
            {
                $user_r = mysql_query("select * from twitter_users where id = '" . $row['track_id'] . "'", $db->connection);
                $tk->log(mysql_error(), 'mysql-checkFilterPredicates-selectusers', "log/stream_" . $stream_id . "_log");
                $user = mysql_fetch_assoc($user_r);

                if ($user["flag"] == 1)
                    $follow[] = $user['twitter_id'];
                
                mysql_free_result($user_r);
                unset($user_r);
            }
        }
        $this->setTrack($track);
        $this->setFollow($follow);

        // update pid and last_ping in process table
        $pid = getmypid();
        mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
        
        mysql_free_result($r);
        unset($r);
    }

    public function log($message, $level = 'notice')
    {
        global $tk;
        global $stream_id;
        
        $tk->log($message, $level, "log/stream_" . $stream_id . "_log");
    }

}

// Start streaming
$sc = new DynamicTrackConsumer($user["token_key"], $user["token_secret"], Phirehose::METHOD_FILTER);
$sc->consume();
