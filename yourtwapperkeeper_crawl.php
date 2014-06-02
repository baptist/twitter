<?php
// load important files
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// setup values
$crawl_log_file = "log/crawl_process_log";
$pid = getmypid();
$sleep = $twitter_api_sleep_sec;
$count = 0;

// Setup connection
$connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $tk_oauth_token, $tk_oauth_token_secret);
$connection->useragent = $youtwapperkeeper_useragent;

date_default_timezone_set("Europe/Brussels");

// update liveness of process
mysql_query("update processes set live = '1' where pid = '$pid'", $db->connection);

while (TRUE)
{
    // Query for archives 
    $q_archives = "select * from archives where type in (1,2,3,4) order by count desc";
    $r_archives = mysql_query($q_archives, $db->connection);
    $counting = 0;

    while ($row_archives = mysql_fetch_assoc($r_archives))
    {
        $tk->log($counting++ . ".   crawling: " . $row_archives['id'] . " - " . $row_archives['keyword'], "", $crawl_log_file);
        $num_inserted = 0;
        // Loop for 15 pages
        $max_id = NULL;

        $type = ($row_archives['type'] == 1 ) ? "" : ($row_archives['type'] == 2) ? "#" : "@";

        for ($page_counter = 1; $page_counter <= 15; $page_counter = $page_counter + 1)
        {

            // sleep for rate limiting
            sleep($sleep);
              

            if ($max_id == NULL)
            {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100, 'result_type' => 'recent'));
            } else
            {
                $search = $connection->get('search/tweets', array('q' => $type . $row_archives['keyword'], 'count' => 100, 'max_id' => $max_id, 'result_type' => 'recent'));
            }

            $searchresult = get_object_vars($search);
            $count = count($searchresult['statuses']);

            // parse results
            foreach ($searchresult['statuses'] as $key => $value)
            {
                $tweet = $tk->extractTweetData($value);

                // duplicate record check and insert into proper cache table if not a duplicate
                $q_check = "select id from z_" . $row_archives['id'] . " where id = '" . $tweet['id'] . "'";
                $result_check = mysql_query($q_check, $db->connection);

                if (mysql_numrows($result_check) == 0)
                {
                    $num_inserted++;
                    $tk->insertTweet($row_archives['id'], $tweet, $row_archives['type'], "search", $crawl_log_file);
                }
                $max_id = $tweet["id"]; // resetting to lowest tweet id
            }

            // If count for page is less than 100, break since there is no reason to keep going
            if ($count < 100)
            {
                break;
            }
        }
    
        // If type is user tracking fetch user tweets through timeline
        if ($row_archives['type'] == 3 || $row_archives['type'] == 4 || $row_archives['type'] == 5)
        {
            $searchresult = $connection->get('statuses/user_timeline', array('screen_name' => $row_archives['keyword']));

            // parse results
            foreach ($searchresult as $key => $value)
            {
                $tweet = $tk->extractTweetData($value);

                //var_dump($tweet);
                // duplicate record check and insert into proper cache table if not a duplicate
                $q_check = "select id from z_" . $row_archives['id'] . " where id = '" . $tweet['id'] . "'";
                $result_check = mysql_query($q_check, $db->connection);

                if (mysql_numrows($result_check) == 0)
                {                    
                    $tk->insertTweet($row_archives['id'], $tweet, $row_archives['type'], "timeline", $crawl_log_file);
                }
            }
        }

        // update counts
        $q_count_total = "select count(id) from z_" . $row_archives['id'];
        $r_count_total = mysql_query($q_count_total, $db->connection);
        $r_count_total = mysql_fetch_assoc($r_count_total);
        $q_update_count_total = "update archives set count = '" . $r_count_total['count(id)'] . "' where id = '" . $row_archives['id'] . "'";
        mysql_query($q_update_count_total, $db->connection);


        // update pid and last_ping in process table
        mysql_query("update processes set last_ping = '" . time() . "' where pid = '$pid'", $db->connection);

        $tk->log("inserted " . $num_inserted, "", $crawl_log_file);
    }
}

?>
