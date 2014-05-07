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

$historyFetchStats = $tk->getHistoryStats(24);
$stats = $tk->getStats();
$tags = $tk->getUniformTags(4);
?>

<?php include("templates/header.php"); ?>



<script>
    $(document).ready(function() {

        getArchives('');

        function getArchives(filter) {
           
            $.ajax({
                type: "POST",
                url: 'get_archives.php',
                data: {
                    'filter': filter
                },
                success: function(data) {
                    $("#archives").html(data);
                }
            });
        }
        
        $(".clickable").click(function(evt) {
            if ($(this).text() !== "")
                getArchives($(this).text());
                
            $(".selected").attr("class", "clickable");
            $(this).attr("class", "selected");
        });
        
        $("#focus").change(function(evt) {
            if ($(this).val() !== "")
                getArchives($(this).val()); 
                   
        });
    });

</script>

<section id="overview-content">


    <!-- NOTIFICATION AREA -->

    <?php
    if ($logged_in)
    {
        ?>
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
                        <?php
                        if (in_array($_SESSION['access_token']['screen_name'], $admin_screen_name))
                        {
                            $archiving_status = $tk->statusLiveArchiving();
                            if ($archiving_status[0] == FALSE)
                            {
                                echo "<li class='" . (($archiving_status[2] == 1) ? "danger" : "caution") . "'>$archiving_status[1] <a href='startarchiving.php'>Start</a></li>";
                            } else
                            {
                                echo "<li class='correct'>$archiving_status[1] <a href='stoparchiving.php'>Stop</a></li>";
                                echo "<li class=''></li>";
                                echo "<li class='infor'>" . "Fetched <span style='font-weight:bold'>" . number_format ($stats["num_tweets"]) . " tweets </span> in total." . "</li>";
                                echo "<li class='infor'>" . "Fetching <span style='font-weight:bold'>" . $stats["avg_tweets"] . " tweets per minute.</span>" . "</li>";
                                echo "<li class='infor'>" . "<span style='font-weight:bold'>Track load: " . $stats["track_load"] . " % -- " . "Follow load: " . $stats["follow_load"] . " % </span>" . "</li>";
                                echo "<li class='infor'>" . "Tracking <span style='font-weight:bold'>" . number_format ($stats["num_hashtags"]) . " hashtags, " . number_format ($stats["num_follows"]) . " users, and " . number_format ($stats["num_conversations"]) . " conversations.</span>" . "</li>";
                            }
                        }
                        /* if (isset($_SESSION['notice'])) {
                          echo "<li class='infor'>" . $_SESSION['notice'] . "</li>";
                          } */
                        ?>
                    </ul>
                </div>

                <?php
                $archiving_status = $tk->statusLiveArchiving();
                if ($archiving_status[0] == TRUE)
                {
                    ?>
                    <div style="float:left; margin-left:250px; margin-top:30px">
                        <span style='font-weight:bold; padding-left:20px;'><img src="resources/icons/icons_0054_Bar-Graph-small.png" style='position:relative;top:2px' /> Tweet Fetch Count</span>
                        <br/>
                        <canvas id="canvas_line" height="150" width="500"></canvas>
                    </div>

                    <script>
                        var data = {
                            labels: [<?php echo implode(",", $historyFetchStats[0]); ?>],
                            datasets: [
                                {
                                    fillColor: "rgba(255,255,255,0.3)",
                                    strokeColor: "rgba(0,0,0,.7)",
                                    pointColor: "rgba(0,0,0,.7)",
                                    pointStrokeColor: "#000",
                                    data: [<?php echo implode(",", $historyFetchStats[1]); ?>]
                                }
                            ]

                        }

                        var options = {
                            //String - Colour of the scale line	
                            scaleLineColor: "rgba(0,0,0,.1)",
                            //Boolean - Whether to show labels on the scale	
                            scaleShowLabels: false,
                            ///Boolean - Whether grid lines are shown across the chart
                            scaleShowGridLines: true,
                            //String - Colour of the grid lines
                            scaleGridLineColor: "rgba(1,1,1,.05)",
                            //Number - Width of the grid lines
                            scaleGridLineWidth: 1,
                            //Boolean - Whether to show a dot for each point
                            pointDot: true,
                            //Number - Radius of each point dot in pixels
                            pointDotRadius: 3,
                            //Number - Pixel width of point dot stroke
                            pointDotStrokeWidth: 1,
                            //Boolean - Whether to show a stroke for datasets
                            datasetStroke: true,
                            //Number - Pixel width of dataset stroke
                            datasetStrokeWidth: 2,
                            //Boolean - Whether to fill the dataset with a colour
                            datasetFill: true
                        }

                        new Chart(document.getElementById("canvas_line").getContext("2d")).Line(data, options);


                    </script>
                    <?php
                }
                ?>



            </div>
        </div>
    <?php } ?>


    <!-- ARCHIVE CREATION AREA -->
    <br/><br/>
    <div class='main'>

        <?php
        if ($logged_in)
        {
            ?>

            <div class="main-block">

                <div style="padding:7px;">

                    <span class="main-header" >Create Archive(s) </span>

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



        <div id="search">
            <ul>
                <li class='clickable'>Top 50</li>

                <?php
                foreach ($tags as $tag)
                    echo "<li class='clickable'>$tag</li>";
                ?>


                <li class="last">
                    <img src="resources/icons/icons_0020_Looking-Glass-small_grey.png"
                         alt=""
                         style="margin-top:2px;margin-right:6px;"/>
                    <input type="text" name="focus" id="focus" value="" style="position:relative;top:-3px;width:150px;" />
                </li>


            </ul>            

        </div>


        <div class="main-block">
            




            <div id="archives">
            </div>


        </div>




    </div>

</section>

<br/>

<?php include("templates/footer.php"); ?>