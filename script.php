<?php

// run over archives

require_once('config.php');
require_once('function.php');

    $q_floating_archives = "select id from archives";
    $rs = mysql_query($q_floating_archives, $db->connection);
	$count = 0;
    while ($row = mysql_fetch_assoc($rs))
    {
	$table = $row["id"];
        
	// run over all tweets

	$q = "select id,time from z_$table limit 50";
	
	$r = mysql_query($q, $db->connection);
	print "NUM: " . mysql_num_rows($r). "\n";
	
	while ($tweet = mysql_fetch_assoc($r))
    {
	print $tweet['id']. " COMPARE " . ((string)$tweet['id']) ."  \n";
$q = "insert into new_tweets values('".((string)$tweet['id'])."', $table, '". $tweet['time'] ."', UNIX_TIMESTAMP(), -1)";
mysql_query($q, $db->connection);
echo "INSERTED $count\n";
$count += 1;

}

    }



?>

