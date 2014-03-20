<?php

/*
  yourTwapperKeeper - Twitter Archiving Application - http://your.twapperkeeper.com
  Copyright (c) 2010 John O'Brien III - http://www.linkedin.com/in/jobrieniii

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Load important files
session_start();
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// validate information before creating
if (!(isset($_SESSION['access_token']['screen_name']))) {
    $_SESSION['notice'] = 'You must login to create an archive.';
    header('Location: index.php');
    die;
}
// TODO create general notification system
// create and redirect
$result = $tk->createArchive($_POST['value'], $_POST['description'], $_POST['tags'], $_SESSION['access_token']['screen_name'], $_SESSION['access_token']['user_id'], $_POST['type']);

// if type is user and perform user lookup
if ($_POST['type'] == 3)
{
    $kill = "kill -9 `ps -ef |grep yourtwapperkeeper_lookup |grep -v grep | awk '{print $2}'`";
    exec($kill);

    $job = 'php '.$tk_your_dir."yourtwapperkeeper_lookup.php";
    $pid = $tk->startProcess($job);
    mysql_query("update processes set pid = '$pid' where process = 'yourtwapperkeeper_lookup'", $db->connection);
}

$_SESSION['notice'] = $result[0];
header('Location: index.php');
?>