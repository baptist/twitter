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

// LOOK AT README FOR HOW TO CONFIGURE!!!!!!!!!!

/* Host Information */
$tk_your_url = "http://localhost/Twitter/TwapperKeeper/";  												// make sure to include the trailing slash
$tk_your_dir = "/home/bvdrsmis/Desktop/Projects/Twitter/TwapperKeeper/";  															// make sure to include the trailing slash
$youtwapperkeeper_useragent = "YourTwapper Keeper";											// change to whatever you want!

/* Administrators - Twitter screen name(s) who can administer / start / stop archiving */
$admin_screen_name=array('BaptistV'); 

/* Users - Twitter screen names that are allowed to use Your Twapper Keeper site - leaving commented means anyone can use site*/
/* $auth_screen_name=array('JohnSmith','SallySue'); */



/* Your Twapper Keeper Twitter Account Information used to query for tweets (this is common for the site) */
$tk_twitter_username = 'BaptistV'; 
$tk_twitter_user_id = '306311209';
$tk_twitter_password = '';
$tk_oauth_token = '306311209-1U8kP6tFXKM5PHCKyK4YLsMAbrSwH0BrWiqN4rys';
$tk_oauth_token_secret = '4OBiM6uv71Vc7SmnOZPbASGoOcGqwzHQEEHPUhJunwsF8'; 

/* Your Twapper Keeper Application Information - setup at http://dev.twitter.com/apps and copy in consumer key and secret */
$tk_oauth_consumer_key = 'zUMbtrvogYuRek1GKhhA';
$tk_oauth_consumer_secret = 'JvKlmAWsBOpHXXx25f1bZDQq3M6Vq7tZsh10cHSk4';

/* MySQL Database Connection Information */                                             
define("DB_SERVER", "localhost");										// change to your hostname
define("DB_USER", "root");									// change to your db username
define("DB_PASS", "");												// change to your db password
define("DB_NAME", "twapperkeeper"); 										// change to your db name


$yourtwapperkeeper_version = "version 0.7.1";
$archive_process_array = array(/*'yourtwapperkeeper_crawl.php',*/ 'yourtwapperkeeper_multiple_streams.php','yourtwapperkeeper_stream_process.php','yourtwapperkeeper_update_tweets.php');
$twitter_api_sleep_sec = ceil(15 * 60 / 180);
$stream_process_stack_size = 500;
$update_stack_size_per_second = 27000 / 3600.0;
$update_after = 6 * 60 * 60; // 12 hours **modified to 6 (TESTING)
$update_time_window = 5 * 60; // 5 minutes
$update_stack_size = floor($update_stack_size_per_second * $update_time_window);
$max_user_streams = 4;
$twitter_keyword_limit_per_stream = 400;
$twitter_follow_limit_per_stream = 5000;
$time_to_track_user = 24 * 60 * 60; // 24 hours to track conversation

$php_mem_limit = "512M";
ini_set("memory_limit", $php_mem_limit);

class MySQLDB
{
   var $connection;      

 function MySQLDB(){
      $this->connection = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die(mysql_error());
      mysql_select_db(DB_NAME, $this->connection) or die(mysql_error());
   }

}
$db = new MySQLDB;

?>
