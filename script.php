<?php

// run over archives

require_once('config.php');
require_once('function.php');

$q_floating_archives = "select id from archives";
$rs = mysql_query($q_floating_archives, $db->connection);
$count = 0;
while ($row = mysql_fetch_assoc($rs))
{
    $c = 0;
    $table = $row["id"];

    // run over all tweets

    $q = "select id,time from z_$table where archivesource = 'twitter-search' AND updated_at IS NULL ";

    $r = mysql_query($q, $db->connection);
    $num =  mysql_num_rows($r);

    while ($tweet = mysql_fetch_assoc($r))
    {

        $q = "insert into new_tweets values('" . ((string) $tweet['id']) . "', $table, '" . $tweet['time'] . "', UNIX_TIMESTAMP(), -1)";
        mysql_query($q, $db->connection);
        if (mysql_error() == "")
        {         
            $c++;
            $count++;
        }
    }
    echo "TABLE $table ==>  INSERTED $c / $num \n";
}
echo "TOTAL INSERTED: $count \n";
?>

