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
    $tags = false;
    if (isset($_POST['tags']))
    {
        $tags_array = $_POST['tags'];
        $tags = "";
        foreach ( $tags_array as $selected )
            $tags .= strtolower($selected) . "|";
        $tags = substr($tags, 0, -1) . "";
    }
    
    $rtfv = false;
    if (isset($_POST["rtfv"]))
        $rtfv = $_POST["rtfv"];
        
    $from = false;
    if (isset($_POST["from"]))
        $from = $_POST["from"];
    
    $to = false;
    if (isset($_POST["$to"]))
        $to = $_POST["to"];
        
    $archives = $tk->listArchivesWithCondition("tags regexp '$tags' AND type = 3 ORDER BY count DESC LIMIT 50");
    //$tweets = $tk->getTweetsFromArchives($archives['results'], strtotime($from),strtotime($to),false,false,false/*nort*/,false,false,false,false,false,false,false,false,false,false, $rtfv, $rtfv);

}

?>

<?php include("templates/header.php"); ?>



<script>
    $(document).ready(function() {


        $('#descriptionSelect').multiselect({
            includeSelectAllOption: true,
            enableFiltering: true,
            maxHeight: 250,
            buttonWidth: 200
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

        <div class="main-block" style="text-align: center; margin:100px 0; ">

            <div style="padding:7px;">

                
                <br/>

                <div class="">

                    <form action='export.php' method='post' >

                        <table>
                            <tr>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>ID</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Description</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Dates</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Retweets &amp; Favorites</td>
                                <td></td>
                            </tr>

                            <tr style="height:60px">
                                <td></td>
                                <td><select name="description"></td>
                                <td>
                                    <select name="tags[]"  class="multiselect"  multiple="multiple" id="descriptionSelect">

                                        <?php
                                        $tags = $tk->getUniformTags(-1);

                                        foreach ($tags as $tag)
                                            echo "<option id=''>" . ucfirst($tag) . "</option>";
                                        ?>

                                    </select>
                                </td>             
                                <td>From <input type="text" name="from" id="from" value="" style="width:100px;"/> to <input type="text" name="to" id="to" value="" style="width:100px"/> </td> 
                                <td><input name='rtfv'/></td> 
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
    
    <div class="main">
        <div class="main-block">
            <span style="font-weight:bold">Number of archives: <?= $archives["count"] ?></span>
            <!--<span class="header-main">Number of tweets: <?= count($tweets) ?></span>-->
            <br/><br/>
            
            <div>
                <?php include("get_archives.php"); ?>
            </div>
            
        </div>
        
        
    </div>
            
            
            



    

</section>

<br/>

<?php include("templates/footer.php"); ?>