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

if($_SERVER['REQUEST_METHOD'] == "POST")  
{
    $condition = "1";
    if (!empty($_POST['tags']))
    {       
        $tags_array = $_POST['tags'];
        $tags = "";
        foreach ( $tags_array as $selected )
            $tags .= strtolower($selected) . "|";
        
            $condition .= " and tags regexp '" . substr($tags, 0, -1) . "'";  
    }
    
    if (!empty($_POST['type']))
    {
        $type_array = $_POST['type'];
        $type = "";
        foreach ( $type_array as $selected )
            $type .= strtolower($selected) . "|";
        
        $condition .= " and type regexp '" . substr($type, 0, -1) . "'";        
    }
    
    if (!empty($_POST["keyword"]) )
        $condition .= " and keyword LIKE '" . $_POST["keyword"] . "'";     
    
    if (!empty($_POST["description"]))
        $condition .= " and description LIKE '" . $_POST["description"] . "'";     
    
    $limit = false;
    if (!empty($_POST["limit"]))
        $limit = $_POST["limit"];
    
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
    $tweets = $tk->getTweetsFromArchives($archives['results'], $from,$to,$limit,false,$no_rt,false,false,false,false,false,false,false,false,false,false, $rt, $fv);
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
        
        
        $('#typesSelect').multiselect({       
            includeSelectAllOption: true,
            numberDisplayed:2,
            maxHeight: 150,
            buttonWidth: 150
        });
        
        $('input[type=submit]').button();
        
        $("#to").datepicker({ dateFormat: 'dd/mm/yy', changeYear: true});
        $("#from").datepicker({ dateFormat: 'dd/mm/yy', changeYear: true});

    });

</script>

<section id="overview-content">

    <?php
    if ($logged_in)
    {
        ?>

        <div class="main-block" style=" margin:100px 0; ">

            <div style="padding:7px;">

                
                <br/>

                <div class="">

                    <form action='export.php' method='post' >

                        <table style="width:100%">
                            <tr>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Type</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Keyword</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Description</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Dates</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>No RT</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Retweets</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Favorites</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Limit</td>
                                <td></td>
                            </tr>

                            <tr style="height:60px">
                                <td>
                                    <select name="type[]"  class="multiselect"  multiple="multiple" id="typesSelect">
                                        <?php
                                        $types = array("keyword", "#hashtag", "@user");
                                        $i = 1;
                                        foreach ($types as $type)
                                            echo "<option value='".$i++."'>" . $type . "</option>";
                                        ?>
                                    </select>
                                </td>
                                <td><input name='keyword'/></td>
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
                                <td><input type="checkbox" name='no_rt' /></td> 
                                <td><input name='rt' style="width:60px"/></td> 
                                <td><input name='fv' style="width:60px"/></td> 
                                <td><input name='limit' style="width:60px"/></td> 
                                <td><input type='submit' class ="submit-button" value ='Filter' class="ui-state-default ui-corner-all"/></td>
                            </tr>
                            
                        </table>
                   

                        <br/>
                        <br/>

                    </form>

                </div>


            </div>

        <?php } ?>
            
            </div>
    
    <?php
        if (isset($archives)) {
    ?>    
        
    
    <div class="main">
        <div class="main-block">
            <span class="main-header">Export archives</span> <br/>
            <span style="font-weight:bold">Number of archives: <?= number_format ($archives["count"]) ?></span> <br/>
            <span style="font-weight:bold">Number of tweets: <?= number_format (count($tweets)) ?></span> <br/>
            
            <form action="excel.php" method="GET">
                
                <input type="submit" value="Export" />
            </form>
                                 
            <br/><br/>
            
          
            
        </div>
        
        
    </div>
    
    <?php
        }
    ?> 
            
            
            



    

</section>

<br/>

<?php include("templates/footer.php"); ?>