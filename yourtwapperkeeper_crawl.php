<?php

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// setup values
$pid = getmypid();
$sleep = $twitter_api_sleep_sec;
$count = 0;

// Setup connection
$connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $tk_oauth_token, $tk_oauth_token_secret);
$connection->useragent = $youtwapperkeeper_useragent;

// TODO Propagate to PHP config files.
date_default_timezone_set ( "UTC" );

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

while (TRUE) {
    // Query for archives 
    $q_archives = "select * from archives";
    $r_archives = mysql_query($q_archives, $db->connection);

    while ($row_archives = mysql_fetch_assoc($r_archives)) {

        // sleep for rate limiting
        echo "sleep = $sleep\n";
        sleep($sleep);

        echo $row_archives['id'] . " - " . $row_archives['keyword'] . "\n";

        // Loop for 15 pages
        $max_id = NULL;

        for ($page_counter = 1; $page_counter <= 15; $page_counter = $page_counter + 1) {
            echo "****TIME AROUND = " . $page_counter . "****\n";

            $type = ($row_archives['type'] == 1 ) ? "" : ($row_archives['type'] == 2) ? "#" : "@";

            if ($max_id == NULL) {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100));
                echo "NO - no max_id is not set\n";
            } else {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100, 'max_id' => $max_id));
                echo "YES - max_id is set\n";
            }


            $searchresult = get_object_vars($search);
            $count = count($searchresult['statuses']);

            // parse results
            foreach ($searchresult['statuses'] as $key => $value) {
                $value = get_object_vars($value);
                
                if (array_key_exists('retweeted_status', $value))
                    $orig_user = get_object_vars(get_object_vars($value['retweeted_status'])["user"]);
                else
                {
                    $orig_user["id"] = "";
                    $orig_user["screen_name"] = "";
                }

                // extract data
                //extract($value,EXTR_PREFIX_ALL,'temp');
                $temp_text = $value['text'];
                $temp_to_user = $value['in_reply_to_screen_name'];
                $temp_to_user_id = $value['in_reply_to_user_id'];                
                $temp_from_user = $value['user']->screen_name;
                $temp_from_user_id = $value['user']->id;
                $temp_id = $value['id_str'];
                $temp_original_user = $orig_user['id'];            
                $temp_original_user_id = $orig_user['screen_name']; 
                $temp_iso_language_code = $value['metadata']->iso_language_code;
                $temp_source = $value['source'];
                $temp_profile_image_url = $value['user']->profile_background_image_url;
                $temp_created_at = $value['created_at'];


                // extract geo information
                if ($value['geo'] != NULL) {
                    $geo = get_object_vars($value['geo']);
                    $geo_type = $geo['type'];
                    $geo_coordinates_0 = $geo['coordinates'][0];
                    $geo_coordinates_1 = $geo['coordinates'][1];
                } else {
                    $geo_type = NULL;
                    $geo_coordinates_0 = 0;
                    $geo_coordinates_1 = 0;
                }

                // duplicate record check and insert into proper cache table if not a duplicate
                $q_check = "select id from z_" . $row_archives['id'] . " where id = '" . $value['id'] . "'";
                $result_check = mysql_query($q_check, $db->connection);

                if (mysql_numrows($result_check) == 0) {
                   
                    $q = "insert into z_".$row_archives['id']." values ('twitter-search','" . mysql_real_escape_string($temp_text) . "','" . $temp_to_user_id . "','" . $temp_to_user . "','" . $temp_from_user_id . "','" . $temp_from_user . "','" . $temp_original_user_id . "','" . $temp_original_user . "','" . $temp_id . "','" . $temp_iso_language_code . "','" . $temp_source . "','" . $temp_profile_image_url . "','" . $geo_type . "','" . $geo_coordinates_0 . "','" . $geo_coordinates_1 . "','" . $temp_created_at . "','" . strtotime($temp_created_at) . "', NULL, NULL, NULL)";
                    mysql_query($q, $db->connection);
                    
                    // add to update stream if tweet is not older than specified number of hours (cf. config).
                    // TODO [WARNING] This could possibly produce a too high amount of tweets to be able to update.
                    if (time() - $temp_created_at < $update_after)
                        mysql_query("insert into new_tweets values('".$temp_id."', '".$row_archives['id']."', '".$temp_created_at."', '-1' )", $db->connection);
                    
                    
                    echo "[" . $row_archives['id'] . "-" . $row_archives['keyword'] . "] $page_counter - $temp_id - insert\n";
                } else {
                    echo "[" . $row_archives['id'] . "-" . $row_archives['keyword'] . "] $page_counter - $temp_id - duplicate\n";
                }
                $max_id = $temp_id; // resetting to lowest tweet id
            }

            // If count for page is less than 100, break since there is no reason to keep going
            if ($count < 100) {
                break;
            }

            echo "\nmaxid = $max_id.\n";
        }

        // update counts
        $q_count_total = "select count(id) from z_" . $row_archives['id'];
        $r_count_total = mysql_query($q_count_total, $db->connection);
        $r_count_total = mysql_fetch_assoc($r_count_total);
        $q_update_count_total = "update archives set count = '" . $r_count_total['count(id)'] . "' where id = '" . $row_archives['id'] . "'";
        mysql_query($q_update_count_total, $db->connection);
        echo "update count\n";

        // update pid and last_ping in process table
        mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
        echo "update pid\n";
    }
}
?>
