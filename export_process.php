<?php

set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*
ob_implicit_flush(true);
ob_end_flush();

header('Content-type: text/html; charset=utf-8');

include_once('config.php');
include_once('function.php');

$tk->reportProgress("Loading");

$data = json_decode($_POST['data'], true);

$conjunction = "or";
$condition = ($conjunction === "and")? "1" : "0";
if (!empty($data["select"]['tags']))
{
    $tags_array = $data["select"]['tags'];
    $tags = "";
    foreach ($tags_array as $selected)
        $tags .= strtolower($selected) . "|";

    $condition .= " $conjunction tags regexp '" . substr($tags, 0, -1) . "'";
}

if (!empty($data["select"]['type']))
{
    $type_array = $data["select"]['type'];
    $type = "";
    foreach ($type_array as $selected)
        $type .= strtolower($selected) . "|";

    $condition .= " $conjunction type regexp '" . substr($type, 0, -1) . "'";
}

if (!empty($data["select"]["keywords"]))
{
    $array = $data["select"]['keywords'];
    $keywords = "";
    foreach ($array as $selected)
        $keywords .= "'" . strtolower(trim($selected)) . "',";

    /* $keywords = ""; 
      $f = fopen("export.csv", "r");
      while ($line = fgets ($f, 4096))
      $keywords .= "'" . strtolower(trim($line)) . "',"; */

    $condition .= " $conjunction keyword in (" . substr($keywords, 0, -1) . ")";
}

if (!empty($data["select"]["description"]))
    $condition .= " $conjunction description LIKE '" . $data["select"]["description"] . "'";

$archives = $tk->listArchivesWithCondition("$condition ORDER BY count DESC");


if ($archives['count'] === 0)
    echo "No matching archive(s) found.";
else
{
    $limit = false;
    if (!empty($data["filter"]["limit"]))
        $limit = $data["filter"]["limit"];

    $no_mentions = false;
    if (!empty($data["filter"]["no mentions"]))
        $no_mentions = $data["filter"]["no mentions"]  === "Yes";

    $no_rt = false;
    if (!empty($data["filter"]["no retweets"]))
        $no_rt = $data["filter"]["no retweets"]  === "Yes";

    $include_reactions = false;
    if (!empty($data["filter"]["include_reactions"]))
        $include_reactions = $data["filter"]["include_reactions"] === "Yes";

    $rt_fv = false;
    if (!empty($data["filter"]["num retweets/favorites"]))
    {        
        if (!$tk->isEmpty($data["filter"]["num retweets/favorites"][1]))        
            $rt_fv = "retweets " . $data["filter"]["num retweets/favorites"][0] . " " . $data["filter"]["num retweets/favorites"][1];
        
        if (!$tk->isEmpty($data["filter"]["num retweets/favorites"][1]) && !$tk->isEmpty($data["filter"]["num retweets/favorites"][4])) 
            $rt_fv .= " " . $data["filter"]["num retweets/favorites"][2] . " ";
                
        if (!$tk->isEmpty($data["filter"]["num retweets/favorites"][4]))        
            $rt_fv .= "favorites " . $data["filter"]["num retweets/favorites"][3] . " " . $data["filter"]["num retweets/favorites"][4];          
    }
            
    $fields = false;
    if (!empty($data["filter"]["fields"]))
        $fields = $data["filter"]["fields"];


    $from = false;$to = false;
    if (!empty($data["filter"]["dates"]))
    {
        if (!empty($data["filter"]["dates"][0]))
            $from = DateTime::createFromFormat('d/m/Y H:i:s', $data["filter"]["dates"][0] . " 00:00:00")->getTimestamp();
        if (!empty($data["filter"]["dates"][1]))
            $to = DateTime::createFromFormat('d/m/Y H:i:s', $data["filter"]["dates"][1] . " 23:59:59")->getTimestamp();
    }

    $tweets = $tk->getTweetsFromArchives($archives['results'], $from, $to, $limit, false, $no_rt, $no_mentions, false, false, false, false, false, false, false, false, false, $rt_fv, $include_reactions);

    if (!empty($data["analyze"]))
    {
        $tk->reportProgress("Analyzing"); 
        
        $stats = array(); 
           
        if (!empty($data["analyze"]["tweet statistics"]))
            $stats = array_merge($stats, $tk->extractTweetStatistics($tweets, $data["analyze"]["tweet statistics"][0], $data["analyze"]["tweet statistics"][1]));
        
        if (!empty($data["analyze"]["user statistics"]))
            $stats = array_merge($stats, $tk->extractUserStatistics($archives, $tweets, $data["analyze"]["user statistics"][0], $data["analyze"]["user statistics"][1]));
        
        if (!empty($data["analyze"]["from-to relations"]) && $data["analyze"]["from-to relations"] === "Yes")
            $stats = array_merge($stats, $tk->extractFromToRelations($tweets));
        
        if (!empty($data["analyze"]["unique users"]) && $data["analyze"]["unique users"] === "Yes")
            $stats = array_merge($stats, $tk->extractUniqueUsers($tweets));
            
        
        $keys = array_keys(array_values($stats)[0]);
        $data = $stats;
    }
    else
    {
        $keys = ($fields) ? $fields : array_merge($tweet_fields, $optional_tweet_fields);
        $data = $tweets;
    }
    
    // General stats
    $num_tweets = count($tweets);
    $num_archives = $archives['count'];
    
    // Reset var
    $archives = NULL;
    $tweets = NULL;
    unset($archives);
    unset($tweets);
    
    $tk->reportProgress("Saving");    
    
    $data_tosave = array();
    foreach ($data as $key => $element)
    {         
        $data_tosave[$element['id']] = array();
        foreach ($keys as $key)
        {
            if (array_key_exists($key, $element))
                $data_tosave[$element['id']][$key] = $element[$key];
            else
                $data_tosave[$element['id']][$key] = "";
        }
        $data[$key] = NULL;
        unset($data[$key]);
    }
    // Reset var
    $data = NULL;
    unset($data);

    $tk->saveExport($data_tosave);
    
    $data_tosave = NULL;
    unset($data_tosave);
    
    echo "Loaded " . $num_tweets . " tweet(s) from " . $num_archives . " archive(s).";
    
    // TODO best way to transfer progress information to root?
    $tk->reportProgress("", true);   
}
?>
