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
?>

<?php include("templates/header.php"); ?>



<script>
    $(document).ready(function() {


    });

</script>

<section id="overview-content">


    <!-- NOTIFICATION AREA -->


    <div class="container-bg left">
        <div class="container-header">
            <div style="padding:3px 10px;">
                <img src="resources/icons/icons_0002_Calendar-today-small.png" height="15" alt="Info" /> <?php echo date("l d F Y"); ?> 
                <span style="display:inline-block;padding-left:10px"></span> <img src="resources/icons/icons_0023_Clock-small.png" height="15" alt="Info" /> <?php echo date("H:i"); ?>
            </div>
        </div>

        <div style="position:relative;">
            <div style="float:left;  padding:15px 25px">
                <ul class="status-list">

                </ul>
            </div>


        </div>
    </div>



    <!-- ARCHIVE CREATION AREA -->
    <br/><br/>
    <div class='main'>

<?php
if ($logged_in)
{
    ?>

            <div class="main-block">

                <div style="padding:7px;">

                    <span class="main-header" >Export </span>

                    <br/>

                    <div class="borderdot">

                        <form action='create.php' method='post' >

                            <table>
                                <tr>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>From Keyword</td>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Type</td>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Description</td>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</td>
                                </tr>

                                <tr>
                                    <td style="width:250px"><input type="text" name="value" /></td>
                                    <td style="width:125px"><select name="type"><option value="1" >keyword</option><option value="2">#hashtag</option><option value="3" >@user</option></select></td>
                                    <td style="width:250px"><input name='description'/></td> 
                                    <td style="width:250px"><input name='tags'/></td> 
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><input type='submit' class ="submit-button" value ='Create Archive(s)' class="ui-state-default ui-corner-all"/></td>
                                </tr>
                            </table>
                            <br/>

                        </form>

                    </div>

                    <div class="borderdot">

                        <form action='createBulk.php' method='post' enctype='multipart/form-data'>

                            <table>
                                <tr>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>From File</td>
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Type</td>  
                                    <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</td>
                                    <td></td>

                                </tr>

                                <tr>
                                    <td style="width:250px"><input type="file" name='file' style="width:250px"/></td>
                                    <td style="width:125px"><select name="type"><option value="1" >keyword</option><option value="2">#hashtag</option><option value="3" >@user</option></select></td>
                                    <td style="width:250px"><input name='tags'/></td>
                                    <td style="width:250px"><input type='submit' class ="submit-button" value ='Create Archive(s)' class="ui-state-default ui-corner-all" /></td> 
                                </tr>

                            </table>
                            <br/>

                        </form>

                    </div>

                </div>
            </div>

<?php } ?>



    </div>

</section>

<br/>

<?php include("templates/footer.php"); ?>