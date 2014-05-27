
<div style='margin:5px'><img src='<?= $tweet['profile_image_url'] ?>' height='40px'/></div>

<?php

$text = preg_replace('@(http://([\w-.]+)+(:\d+)?(/([\w/_.]*(\?\S+)?)?)?)@', '<a href="$1" target="_blank">$1</a>', $tweet['text']);
$matches = array();
preg_match('@(http://([\w-.]+)+(:\d+)?(/([\w/_.]*(\?\S+)?)?)?)@', $tweet['text'], $matches);
$text = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $text);


//preg_replace('#','<a href="http://search.twitter.com/q=$1">.$1."</a>');
echo "<span style='font-weight:bold'>@" . $tweet['from_user'] . "</span> <span>" . $text . "</span><br/>";
echo "<span style='font-weight:lighter; font-size:8px; font-style:italic; display:inline-block'>" . $tweet['created_at'] . " - tweet id <a name='tweetid-" . $tweet['id'] . "' href='conversation.php?id=" . $tweet['id'] . "'>" . $tweet['id'] . "</a> - #$tw_count</span>";

if ($tweet['retweets'] != '' || $tweet['favorites'] != '')
    echo "<span style='font-size:80%; float:right; display:inline-block; margin-right:50px'>RETWEETS <span style='font-weight:bold; display:inline-block'>" . $tweet['retweets'] . "</span>  |  FAVORITES <span style='font-weight:bold; display:inline-block'>" . $tweet['favorites'] . "</span></span>";


if ($tweet['geo_type'] <> '')
{
    echo "<font style='font-weight:lighter; font-size:8px'><i>geo info: " . $tweet['geo_type'] . " - lat = " . $tweet['geo_coordinates_0'] . " - long = " . $tweet['geo_coordinates_1'] . "</i></font><br>";
}
?>

