<?php

// Load important files
session_start();
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// Ensure user is an administrator
if (!(in_array($_SESSION['access_token']['screen_name'], $admin_screen_name))) {
    $_SESSION['notice'] = "Only administrators are allowed to stop / start archiving processes";
    header('Location:index.php');
    die;
}

// List of archiving scripts
//$cmd = $archive_process_array;
$cmd = array();
$r = mysql_query("select process from processes where live = 1", $db->connection);
while ($a = mysql_fetch_assoc($r))
    $cmd[] = $a["process"];

// Query PIDS and kill jobs
foreach ($cmd as $key => $value) {
    $pid = mysql_fetch_assoc(mysql_query("select pid from processes where process = '$value'", $db->connection));
    $pid = $pid['pid'];
    $tk->killProcess($pid);
    $pids .= $pid . ",";
    mysql_query("update processes set pid = '0', live = '0' where process = '$value'", $db->connection);
}
$pids = substr($pids, 0, -1);


$_SESSION['notice'] = "Twitter archiving processes have been stopped. (PIDs = $pids)";
header('Location:index.php');
?>
