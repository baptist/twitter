<?php

// TODO decide upon adding tweets from users also to hashtag archives!

// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth_search.php');

// setup values
$pid = getmypid();
$script_key = uniqid();

// process loop
while (TRUE) {
    // lock up some tweets
    $q = "update rawstream set flag = '$script_key' where flag = '-1' limit $stream_process_stack_size";
    echo $q . "\n";
    mysql_query($q, $db->connection);

    // get keyword into memory
    $q = "select id,keyword,track_id,type from archives";
    echo $q . "\n";
    $r = mysql_query($q, $db->connection);
    $track = array();
    $follow = array();
        
    // TODO decide which has priority over the other (user > hashtag > keyword ?)
    while ($row = mysql_fetch_assoc($r)) {
        if ($row["type"] == 1)
            $track[$row['id']] = $row['keyword'];
        else if ($row["type"] == 2)
            $track[$row['id']] = "#" . $row['keyword'];
        else if ($row["type"] == 3)
        {
            $follow[$row['id']] = $row['keyword'];
            $track[$row['id']] = "@" . $row['keyword'];
        }               
    }

    // grab the locked up tweets and load into memory
    $q = "select * from rawstream where flag = '$script_key'";
    $r = mysql_query($q, $db->connection);
    echo $q . "\n";
    $batch = array();
    while ($row = mysql_fetch_assoc($r)) {
        $batch[] = $row;
    }

    // for each tweet in memory, compare against predicates and insert
    foreach ($batch as $tweet) 
    {
        //echo "[" . $tweet['id'] . " - " . $tweet['text'] . "]\n";
        $inserted = FALSE;
        foreach ($follow as $ztable => $user) 
        {
            if (strcasecmp($user, $tweet['from_user'])  == 0)
            {
                $inserted = insert($ztable, $tweet, "creator");
                break;
            }
            else if (strcasecmp($user, $tweet['to_user']) == 0)
            {
                $inserted = insert($ztable, $tweet, "reply");
                break;
            }
            else if (strcasecmp($user, $tweet['original_user'])  == 0)
            {
                $inserted = insert($ztable, $tweet, "retweet");
                break;
            }
            else if (strcasecmp($user, substr(explode(" ", $tweet['text'])[0], 1))  == 0)
            {
                $inserted = insert($ztable, $tweet, "mention");
                break;
            }
        }

        if (!$inserted) 
        {
            foreach ($track as $ztable => $keyword) {
                
                if (stristr($tweet['text'], $keyword) == TRUE) {
                    echo " vs. $keyword = insert\n";
                    insert($ztable, $tweet, "keyword");
                } else {
                    //echo " vs. $keyword = not found\n";
                }
            }
        }
        echo "---------------\n";
    }

    // delete tweets in flag
    $q = "delete from rawstream where flag = '$script_key'";
    //echo $q . "\n";
    mysql_query($q, $db->connection);

    // update counts

    foreach ($follow as $ztable => $keyword) {
        $q_count = "select count(id) from z_$ztable";
        $r_count = mysql_query($q_count, $db->connection);
        $r_count = mysql_fetch_assoc($r_count);
        $q_update = "update archives set count = '" . $r_count['count(id)'] . "' where id = '$ztable'";
        //echo $q_update . "\n";
        mysql_query($q_update, $db->connection);
    }
    
    foreach ($track as $ztable => $keyword) {
        $q_count = "select count(id) from z_$ztable";
        $r_count = mysql_query($q_count, $db->connection);
        $r_count = mysql_fetch_assoc($r_count);
        $q_update = "update archives set count = '" . $r_count['count(id)'] . "' where id = '$ztable'";
        //echo $q_update . "\n";
        mysql_query($q_update, $db->connection);
    }

    // update pid and last_ping in process table
    mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);
    //echo "update pid\n";
}

function insert($table_id, $tweet, $reason = "") {
    global $db;
    
    $q_insert = "insert into z_$table_id values ('twitter-stream-$reason','" . $tweet['text'] . "','" . $tweet['to_user_id'] . "','" . $tweet['to_user'] . "','" . $tweet['from_user_id'] . "','" . $tweet['from_user'] . "','" . $tweet['original_user_id'] . "','" . $tweet['original_user'] . "','" . $tweet['id'] . "','" . $tweet['iso_language_code'] . "','" . $tweet['source'] . "','" . $tweet['profile_image_url'] . "','" . $tweet['geo_type'] . "','" . $tweet['geo_coordinates_0'] . "','" . $tweet['geo_coordinates_1'] . "','" . $tweet['created_at'] . "','" . $tweet['time'] . "', NULL, NULL)";
    $r_insert = mysql_query($q_insert, $db->connection);
    
    $q2 = "insert into new_tweets values('".$tweet['id']."', $table_id, '". $tweet['time'] ."', -1)";    
    $result = mysql_query($q2, $db->connection);
    
    return TRUE;
}

?>
