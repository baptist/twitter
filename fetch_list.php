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


for ($page_counter = 1; $page_counter <= 100; $page_counter = $page_counter + 1) {

    echo "****TIME AROUND = " . $page_counter . "****\n";

    if ($max_id == NULL) {
        $search = $connection->get('lists/members', array('owner_screen_name' => 'VlaamseTweeps', 'slug' => 'vlaamsetweeps'));
    } else {
        $search = $connection->get('lists/members', array('owner_screen_name' => 'VlaamseTweeps', 'slug' => 'vlaamsetweeps', 'cursor' => $max_id));
    }

    $searchresult = get_object_vars($search);
    $users = $searchresult["users"];

    foreach ($users as $user)
    {
        $user = get_object_vars($user);
        file_put_contents("parsed_users", $user["screen_name"] . "\n", FILE_APPEND);
    }
    $max_id = $searchresult["next_cursor_str"];

    
}

     
?>
