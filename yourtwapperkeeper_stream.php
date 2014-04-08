<?php
require_once('Phirehose.php');
require_once('OauthPhirehose.php');
require_once('config.php');
require_once('function.php');

define('TWITTER_CONSUMER_KEY',$tk_oauth_consumer_key);
define('TWITTER_CONSUMER_SECRET',$tk_oauth_consumer_secret);

class DynamicTrackConsumer extends OauthPhirehose
{ 
  
  public function enqueueStatus($status)
  {        
  	global $db;
   	$status = json_decode($status);                 
        $status = get_object_vars($status);
        var_dump($status);
        // TODO check if error message is send back!
        if ($status['id'] <> null) {

            $values_array = array();

            $geo = get_object_vars($status['geo']); 
            $user= get_object_vars($status['user']);
            
            if (array_key_exists('retweeted_status', $status))
                $orig_user = get_object_vars(get_object_vars($status['retweeted_status'])["user"]);
            else
            {
                $orig_user["id"] = "";
                $orig_user["screen_name"] = "";
            }

            $values_array[] = "-1";                                     // processed_flag [-1 = waiting to be processed]
            $values_array[] = $status['text'];                          // text
            $values_array[] = $status['in_reply_to_user_id'];           // to_user_id
            $values_array[] = $status['in_reply_to_screen_name'];       // to_user
            $values_array[] = $user['id'];                              // from_user_id
            $values_array[] = $user['screen_name'];                     // from_user 
            $values_array[] = $orig_user['id'];                         // original_user_id
            $values_array[] = $orig_user['screen_name'];                // original_user          
            $values_array[] = $status['id'];                            // id -> unique id of tweet             
            $values_array[] = $user['lang'];                            // iso_language_code
            $values_array[] = $status['source'];                        // source
            $values_array[] = $user['profile_image_url'];               // profile_img_url
            $values_array[] = $geo['type'];                             // geo_type 
            $values_array[] = $geo['coordinates'][0];                   // geo_coordinates_0
            $values_array[] = $geo['coordinates'][1];                   // geo_coordinates_1
            $values_array[] = $status['created_at'];                    // created_at
            $values_array[] = strtotime($status['created_at']);         // time

            $values = '';
            foreach ($values_array as $insert_value) {
            $values .= "'$insert_value',";
            }
            $values = substr($values,0,-1);   

            // add to list of newly created tweets.
            $q1 = "insert into rawstream values($values)";
            $result = mysql_query($q1, $db->connection);            
            echo ".";
        }
  }
 
  public function checkFilterPredicates()
  {
        global $db;
   
        $q = "select id,keyword,type,track_id from archives";
        $r = mysql_query($q, $db->connection);

        $track = array();
        $follow = array();
  	while ($row = mysql_fetch_assoc($r)) {
            
            if ($row["type"] == 1)
                $track[] = $row['keyword'];
            else if ($row["type"] == 2)
                $track[] = "#" . $row['keyword'];
            else if ($row["type"] == 3)
            {               
                // find user                
                $user_r = mysql_query("select * from twitter_users where id = '".$row['track_id']."'", $db->connection);
                $user = mysql_fetch_assoc($user_r);                
               
                if ($user["flag"] == 1)
                {
                    $track[] = "@" . $row['keyword'];
                    $follow[] = $user['twitter_id'];
                }
            }             
  	}
  	$this->setTrack($track); 
        $this->setFollow($follow);
  	
  	// update pid and last_ping in process table
  	$pid = getmypid();
	mysql_query("update processes set last_ping = '".time()."' where pid = '$pid'", $db->connection);
	echo "update pid\n";
  }  
}

// Start streaming
$sc = new DynamicTrackConsumer($tk_oauth_token, $tk_oauth_token_secret, Phirehose::METHOD_FILTER);
$sc->consume();