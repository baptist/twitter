<?php

// run over archives

require_once('config.php');
require_once('function.php');

// get keyword into memory
$q = "select id,keyword,track_id,type from archives";
echo $q . "\n";
$r = mysql_query($q, $db->connection);
if (mysql_error() != "")
    print "Error when selecting archives: " . mysql_error();

print "Num archives fetched: " . mysql_num_rows($r);

$track = array();
$follow = array();

while ($row = mysql_fetch_assoc($r))
{
    if ($row["type"] == 1)
        $track[$row['id']] = $row['keyword'];
    else if ($row["type"] == 2)
        $track[$row['id']] = "#" . $row['keyword'];
    else if ($row["type"] == 3)
    {
        $follow[$row['id']] = $row['keyword'];
        $track[$row['id']] = "@" . $row['keyword'];
    } else if ($row["type"] == 4)
        $follow[$row['id']] = $row['keyword'];
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
    $q = "select * from z_$table";

    $r = mysql_query($q, $db->connection);
    $num = mysql_num_rows($r);
    
    print " PROCESSING $num tweets. \n";

    while ($tweet = mysql_fetch_assoc($r))
    {
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
    }
}

function insert($table_id, $tweet, $type, $reason = '', $log_file = 'log/function_log') {
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

