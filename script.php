<?php

// run over archives

require_once('config.php');
require_once('function.php');

mysql_query("update smart_tweets set is_retweet = 0 , original_id = NULL where original_id = 0 or original_id IS NULL", $db->connection);

// get keyword into memory
$q = "select tweet_id, original_archive, original_id from smart_tweets where NOT (original_id = '' OR original_id IS NULL)";
$r = mysql_query($q, $db->connection);

while ($row = mysql_fetch_assoc($r))
{
    $a = $row["original_archive"];
    $b = $row["original_id"];
    $c = $row["tweet_id"];
    //print "update z_$a set in_reply_to_status_id = '$b' where id = '$c' \n";

    $tweet = mysql_fetch_assoc(mysql_query("select * from z_$a where id='$c'", $db->connection));
    $orig = mysql_fetch_assoc(mysql_query("select * from z_$a where id='$b'", $db->connection));

    if (!empty($tweet))
    {
        if (!empty($orig))
        {
            // registered as retweet but no similar tweets found
            if (strcasecmp($tweet["text"], "RT @" . $orig["from_user"] . ": " . $orig["text"]) != 0)
            {

                if ($tweet["to_user"] === $orig["from_user"])
                {
                    mysql_query("update z_$a set in_reply_to_status_id = '$b' where id = '$c'", $db->connection);
                    mysql_query("update smart_tweets set original_id = NULL, is_retweet = 0, is_duplicate = 0 where tweet_id = '$c'", $db->connection);
                } else
                {
                    print $tweet["id"] . " ===> no match between users! \n";
                }
            }
        } else
        {
            // check archive source

            if (!empty($tweet["to_user"]))
            {
                mysql_query("update z_$a set in_reply_to_status_id = '$b' where id = '$c'", $db->connection);
                mysql_query("update smart_tweets set original_id = NULL, is_retweet = 0, is_duplicate = 0 where tweet_id = '$c'", $db->connection);
            }
        }
    }
}


$q_floating_archives = "select id from archives";
$rs = mysql_query($q_floating_archives, $db->connection);
$count = 0;
while ($row = mysql_fetch_assoc($rs))
{
    $c = 0;

    print "$count. ARCHIVE -> " . $row["id"] . " -->";
    $count++;
    $table = $row["id"];

    // run over all tweets
    $q = "select * from z_$table where NOT (original_user = '' OR original_user IS NULL or in_reply_to_status_id = '' OR in_reply_to_status_id IS NULL)";

    $r = mysql_query($q, $db->connection);
    $num = mysql_num_rows($r);

    print " PROCESSING $num tweets. \n";

    while ($tweet = mysql_fetch_assoc($r))
    {
        
        mysql_query("update z_$table set in_reply_to_status_id = '' where id = '".$tweet['id']."'", $db->connection);
        mysql_query("update smart_tweets set original_id = '".$tweet['in_reply_to_status_id']."', is_retweet = 1 where tweet_id = '".$tweet['id']."'", $db->connection);
        
        
    }
    /*
      foreach ($follow as $ztable => $user)
      {
      if (strcasecmp($user, $tweet['from_user']) == 0)
      $inserted = insert($ztable, $tweet, -1, "stream-creator");

      else if (strcasecmp($user, $tweet['to_user']) == 0)
      $inserted = insert($ztable, $tweet, -1, "stream-reply");

      else if (strcasecmp($user, $tweet['original_user']) == 0)
      $inserted = insert($ztable, $tweet, -1, "stream-retweet");

      else if (strcasecmp($user, substr(explode(" ", $tweet['text'])[0], 1)) == 0)
      $inserted = insert($ztable, $tweet, -1, "stream-mention");
      }


      foreach ($track as $ztable => $keyword)
      {
      if (stristr(strtolower($tweet['text']), strtolower($keyword)) == TRUE)
      $inserted = insert($ztable, $tweet, ($keyword[0] == "#") ? 2 : -1, "stream-keyword");
      }
      } */
}

function insert($table_id, $tweet, $type, $reason = '', $log_file = 'log/function_log')
{
    global $db;
    global $tk;


    $q = "insert into z_$table_id values ('twitter-$reason','" . $tk->sanitize($tweet['text']) . "','" . ((string) $tweet['to_user_id']) . "','" . $tweet['to_user'] . "','" . ((string) $tweet['from_user_id']) . "','" . $tweet['from_user'] . "','" . ((string) $tweet['original_user_id']) . "','" . $tweet['original_user'] . "','" . ((string) $tweet['id']) . "','" . ((string) $tweet['in_reply_to_status_id']) . "','" . $tweet['iso_language_code'] . "','" . $tweet['source'] . "','" . $tweet['profile_image_url'] . "','" . $tweet['geo_type'] . "','" . $tweet['geo_coordinates_0'] . "','" . $tweet['geo_coordinates_1'] . "','" . $tweet['created_at'] . "','" . $tweet['time'] . "', NULL, NULL, NULL)";
    mysql_query($q, $db->connection);


    //if (mysql_error() != "")
    //    echo "Error when inserting into archive $table_id " . mysql_error() . " \n";
    // Insert into central tweets table
    $duplicate = $tk->addSmartTweet($tweet, $table_id, $log_file);
}
?>

