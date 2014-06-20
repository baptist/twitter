<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 30000);

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

// Set Important / Load important
session_start();
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

// OAuth login check
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret']))
{
    $login_status = "<a href='./oauthlogin.php' ><img src='./resources/lighter.png'/></a>";
    $logged_in = FALSE;
} else
{
    $access_token = $_SESSION['access_token'];
    $connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $login_info = $connection->get('account/verify_credentials');
    $login_status = "Hi " . $_SESSION['access_token']['screen_name'] . ", are you ready to archive?<br><a href='./clearsessions.php'>logout</a>";
    $logged_in = TRUE;
}

if ($_SERVER['REQUEST_METHOD'] == "POST")
{
    $condition = "1";
    if (!empty($_POST['tags']))
    {
        $tags_array = $_POST['tags'];
        $tags = "";
        foreach ($tags_array as $selected)
            $tags .= strtolower($selected) . "|";

        $condition .= " and tags regexp '" . substr($tags, 0, -1) . "'";
    }

    if (!empty($_POST['type']))
    {
        $type_array = $_POST['type'];
        $type = "";
        foreach ($type_array as $selected)
            $type .= strtolower($selected) . "|";

        $condition .= " and type regexp '" . substr($type, 0, -1) . "'";
    }

    if (!empty($_POST["keywords"]))
    {
        $array = $_POST['keywords'];
        $keywords = "";
        foreach ($array as $selected)
            $keywords .= "'" . strtolower($selected) . "',";

        /* $keywords = ""; 
          $f = fopen("export.csv", "r");
          while ($line = fgets ($f, 4096))
          $keywords .= "'" . strtolower(trim($line)) . "',"; */

        $condition .= " and keyword in (" . substr($keywords, 0, -1) . ")";
    }

    if (!empty($_POST["description"]))
        $condition .= " and description LIKE '" . $_POST["description"] . "'";

    $limit = false;
    if (!empty($_POST["limit"]))
        $limit = $_POST["limit"];

    $no_mentions = false;
    if (!empty($_POST["no_mentions"]))
        $no_mentions = $_POST["no_mentions"];

    $rt = false;
    if (!empty($_POST["rt"]))
        $rt = $_POST["rt"];

    $fv = false;
    if (!empty($_POST["fv"]))
        $fv = $_POST["fv"];

    $no_rt = false;
    if (!empty($_POST["no_rt"]))
        $no_rt = $_POST["no_rt"];

    $from = false;
    if (!empty($_POST["from"]))
        $from = DateTime::createFromFormat('d/m/Y H:i:s', $_POST["from"] . " 00:00:00")->getTimestamp();

    $to = false;
    if (!empty($_POST["to"]))
        $to = DateTime::createFromFormat('d/m/Y H:i:s', $_POST["to"] . " 23:59:59")->getTimestamp();

    $archives = $tk->listArchivesWithCondition("$condition ORDER BY count DESC");
    $tweets = $tk->getTweetsFromArchives($archives['results'], $from, $to, $limit, false, $no_rt, $no_mentions, false, false, false, false, false, false, false, false, false, $rt, $fv);


    $groupings = false;
    $process = false;
    if (!empty($_POST["groupings"]))
    {        
        $stats = array();

        $user_stats = false;
        if (!empty($_POST["user_stats"]))
            $user_stats = $_POST["user_stats"];

        $tweet_stats = false;
        if (!empty($_POST["tweets_stats"]))
            $tweet_stats = $_POST["tweets_stats"];

        $process = true;

        foreach ($_POST["groupings"] as $grouping)
        {            
            if (strcasecmp($grouping, "user") === 0)
            {
                foreach ($archives['results'] as $archive)
                {
                    $key = strtolower($archive['keyword']);                   
                    $stats[$key] = array();
                    $user = $tk->getUser($key);
                    $stats[$key]['name'] = $user['full_name'];
                    $stats[$key]['followers'] = $user['followers'];
                    $stats[$key]['num_tweets_sent'] = 0;
                    $stats[$key]['num_retweets_sent'] = 0;
                    $stats[$key]['num_replies_sent'] = 0;
                    $stats[$key]['num_retweets_rec'] = 0;
                    $stats[$key]['num_replies_rec'] = 0;
                    $stats[$key]['num_favorites_rec'] = 0;
                }

                foreach ($tweets as $tweet)
                {
                    $key = strtolower($tweet['from_user']);                 
                    if (array_key_exists($key, $stats))
                    { // tweet from user
                        $stats[$key]['num_tweets_sent']++;

                        if ($tweet['to_user'] !== '' && $tweet['to_user'] != NULL)
                            $stats[$key]['num_replies_sent']++;
                        else if ($tweet['original_user'] !== '' && $tweet['original_user'] != NULL)
                            $stats[$key]['num_retweets_sent']++;
                        
                        $stats[$key]['num_favorites_rec'] += $tweet['favorites'];
                        $stats[$key]['num_retweets_rec'] += $tweet['retweets'];
                    } else if (array_key_exists(strtolower($tweet['to_user']), $stats))
                    {
                        $stats[strtolower($tweet['to_user'])]['num_replies_rec']++;
                    }
                }                
            }
        }
        $_SESSION['stats'] = $stats;
    }
    $_SESSION['tweets'] = $tweets;
}
?>

<?php include("templates/header.php"); ?>



<script>
    $(document).ready(function() {


        $('#tagsSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            enableFiltering: true,
            maxHeight: 250,
            buttonWidth: 220
        });

        $('#keywordsSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            enableFiltering: true,
            maxHeight: 250,
            buttonWidth: 220
        });


        $('#typesSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 2,
            maxHeight: 150,
            buttonWidth: 150
        });

        $('#groupingSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 2,
            maxHeight: 150,
            buttonWidth: 150
        });

        $('input[type=submit]').button();

        $("#to").datepicker({dateFormat: 'dd/mm/yy', changeYear: true});
        $("#from").datepicker({dateFormat: 'dd/mm/yy', changeYear: true});

    });

</script>

<section id="overview-content">

    <?php
    if ($logged_in)
    {
        ?>

        <form action='export.php' method='post' >

            <p class ="title" style="margin:100px 0 10px 0;">
                Select
            </p>

            <div class="main-block" style="">

                <div style="padding:7px;">


                    <br/>

                    <div class="">



                        <table style="width:100%">
                            <tr>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Type</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Keyword</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Description</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Dates</td>             
                                <td></td>
                            </tr>

                            <tr style="height:60px">
                                <td>
                                    <select name="type[]"  class="multiselect"  multiple="multiple" id="typesSelect">
                                        <?php
                                        $types = array("keyword", "#hashtag", "@user");
                                        $i = 1;
                                        foreach ($types as $type)
                                            echo "<option value='" . $i++ . "'>" . $type . "</option>";
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="keywords[]"  class="multiselect"  multiple="multiple" id="keywordsSelect">

                                        <?php
                                        $keys = $tk->getKeywords(-1);

                                        foreach ($keys as $key)
                                            echo "<option value='$key'>" . ucfirst($key) . "</option>";
                                        ?>

                                    </select>                                    
                                </td>
                                <td><input name='description'/></td>
                                <td>
                                    <select name="tags[]"  class="multiselect"  multiple="multiple" id="tagsSelect">

                                        <?php
                                        $tags = $tk->getUniformTags(-1);

                                        foreach ($tags as $tag)
                                            echo "<option value='$tag'>" . ucfirst($tag) . "</option>";
                                        ?>

                                    </select>
                                </td>             
                                <td>From <input type="text" name="from" id="from" value="" style="width:100px;"/> to <input type="text" name="to" id="to" value="" style="width:100px"/> </td> 

                            </tr>

                        </table>


                        <br/>
                        <br/>



                    </div>


                </div>



            </div>

            <p class ="title" style="margin:100px 0 10px 0;">
                Filter
            </p>


            <div class="main-block" >

                <div style="padding:7px;">


                    <br/>

                    <div class="">



                        <table style="width:100%">
                            <tr>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>No RT</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>No Mentions</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Retweets</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Favorites</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Limit</td>
                            </tr>

                            <tr style="height:60px">
                                <td><input type="checkbox" name='no_rt' /></td> 
                                <td><input type="checkbox" name='no_mentions' /></td> 
                                <td><input name='rt' style="width:60px"/></td> 
                                <td><input name='fv' style="width:60px"/></td> 
                                <td><input name='limit' style="width:60px"/></td>                                 
                            </tr>

                        </table>


                        <br/>
                        <br/>



                    </div>


                </div>



            </div>


            <p class ="title"  style="margin:50px 0 10px 0;">
                Process
            </p>

            <div class="main-block">

                <div style="padding:7px;">


                    <br/>

                    <div class="">



                        <table style="width:100%">
                            <tr>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Aggregate per</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Count Tweets Stats</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Count User Stats</td>

                            </tr>

                            <tr style="height:60px">
                                <td>
                                    <select name="groupings[]"  class="multiselect"  multiple="multiple" id="groupingSelect">

                                        <?php
                                        $groupings = array("total", "user", "year", "week", "day", "hour");

                                        foreach ($groupings as $grouping)
                                            echo "<option value='$grouping'>" . ucfirst($grouping) . "</option>";
                                        ?>

                                    </select>
                                </td>      
                                <td><input type="checkbox" name='tweets_stats'/></td> 
                                <td><input type="checkbox" name='user_stats' /></td> 

                            </tr>

                        </table>


                        <br/>
                        <br/>



                    </div>


                </div>



            </div>

            <input type='submit' class ="submit-button" value ='Filter' class="ui-state-default ui-corner-all"/>

        </form>

        <?php
        if (isset($archives))
        {
            ?>    


            <div class="main">
                <div class="main-block">
                    <span class="main-header">Export archives</span> <br/>
                    <span style="font-weight:bold">Number of archives: <?= number_format($archives["count"]) ?></span> <br/>
                    <span style="font-weight:bold">Number of tweets: <?= number_format(count($tweets)) ?></span> <br/>

                    <form action="excel.php" method="GET">

                        <input type="submit" value="Export" />
                    </form>

                    <br/><br/>



                </div>


            </div>

            <?php
        }
        ?> 

    <?php } ?>








</section>

<br/>

<?php include("templates/footer.php"); ?>