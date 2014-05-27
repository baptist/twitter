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

if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $tweets = $tk->getConversation($id);
} else
{
    $_SESSION['notice'] = "Archive does not exist.";
    header('Location: index.php');
}


// OAuth login check
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret']))
{
    $login_status = "<a href='./oauthlogin.php' ><img src='./resources/lighter.png'/></a>";
    $logged_in = FALSE;
} else
{
    $access_token = $_SESSION['access_token'];
    $connection = new TwitterOAuth($tk_oauth_consumer_key, $tk_oauth_consumer_secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $logged_in = TRUE;
}
?>

<?php include("templates/header.php"); ?>



<script>
    $(document).ready(function() {


    });

</script>

<section id="overview-content">


    <div class='main'>


        <div class="main-block" style="margin:100px 0">

            <?php
            $tw_count = 0;

            foreach ($tweets as $tweet)
            {

                $tw_count = $tw_count + 1;
                ?>

                <div style='margin-bottom:5px'>
                    <div style='width:950px'>
                        
                        <?php
                        include("templates/tweet.php");
                        
                        ?>
                    </div>
                </div>
                
                
                <?php } ?>

        </div>
    </div>


</section>

<br/>

<?php include("templates/footer.php"); ?>