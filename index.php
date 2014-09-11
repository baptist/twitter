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

$historyFetchStats = $tk->getHistoryStats(2 * 24);
$stats = $tk->getStats();
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

        $("input[type=submit],button").button();


        var data = {
            labels: [<?php echo implode(",", $historyFetchStats[0]); ?>],
            datasets: [
                {
                    fillColor: "rgba(142, 193, 218,0.3)",
                    strokeColor: "rgba(0,0,0,.7)",
                    pointColor: "rgba(0,0,0,.7)",
                    pointStrokeColor: "#000",
                    data: [<?php echo implode(",", $historyFetchStats[1]); ?>]
                }
            ]

        }

        var data2 = [
            {
                value: <?php echo $stats["num_hashtags"]; ?>,                
                color: "#46BFBD",
                highlight: "#5AD3D1",
                label: "Hashtags"
            },
            {
                value: <?php echo $stats["num_follows"]; ?>,
                color: "#46BFBD",
                highlight: "#5AD3D1",
                label: "Users"
            },
            {
                value: <?php echo $stats["num_keywords"]; ?>,
                color: "#46BFBD",
                highlight: "#535966",
                label: "Keywords"
            },
            {
                value: <?php echo $stats["num_conversations"]; ?>,
                color: "#FDB45C",
                highlight: "#FFC870",
                label: "Conversations"
            }
        ]

        var data3 = [
            {
                value: <?php echo $stats["track_load"]; ?>,
                color: "#FDB45C",
                highlight: "#FFC870",
                label: "Track Load"
            },
            {
                value: <?php echo (100 - $stats["track_load"]); ?>,
                color: "rgba(230,230,230,.4)",
                highlight: "rgba(230,230,230,.2)",
                label: "Free"
            }
        ]

        var data4 = [
            {
                value: <?php echo round($stats["follow_load"]); ?>,
                color: "#FDB45C",
                highlight: "#FFC870",
                label: "Follow Load"
            },
            {
                value: <?php echo (100 - $stats["follow_load"]); ?>,
                color: "rgba(230,230,230,.4)",
                highlight: "rgba(230,230,230,.2)",
                label: "Free"
            }
        ]



        Chart.defaults.global = {
            // Boolean - Whether to animate the chart
            animation: true,
            // Number - Number of animation steps
            animationSteps: 60,
            // String - Animation easing effect
            animationEasing: "easeOutQuart",
            // Boolean - If we should show the scale at all
            showScale: false,
            // Boolean - If we want to override with a hard coded scale
            scaleOverride: false,
            // String - Colour of the scale line
            scaleLineColor: "rgba(0,0,0,.05)",
            // Number - Pixel width of the scale line
            scaleLineWidth: 1,
            // Boolean - Whether to show labels on the scale
            scaleShowLabels: false,
            // Interpolated JS string - can access value
            scaleLabel: "<%=value%>",
            // Boolean - Whether the scale should stick to integers, not floats even if drawing space is there
            scaleIntegersOnly: true,
            // Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
            scaleBeginAtZero: true,
            // String - Scale label font declaration for the scale label
            scaleFontFamily: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
            // Number - Scale label font size in pixels
            scaleFontSize: 12,
            // String - Scale label font weight style
            scaleFontStyle: "normal",
            // String - Scale label font colour
            scaleFontColor: "#666",
            // Boolean - whether or not the chart should be responsive and resize when the browser does.
            responsive: false,
            // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
            maintainAspectRatio: true,
            // Boolean - Determines whether to draw tooltips on the canvas or not
            showTooltips: true,
            // Array - Array of string names to attach tooltip events
            tooltipEvents: ["mousemove", "touchstart", "touchmove"],
            // String - Tooltip background colour
            tooltipFillColor: "rgba(0,0,0,0.8)",
            // String - Tooltip label font declaration for the scale label
            tooltipFontFamily: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
            // Number - Tooltip label font size in pixels
            tooltipFontSize: 14,
            // String - Tooltip font weight style
            tooltipFontStyle: "normal",
            // String - Tooltip label font colour
            tooltipFontColor: "#fff",
            // String - Tooltip title font declaration for the scale label
            tooltipTitleFontFamily: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
            // Number - Tooltip title font size in pixels
            tooltipTitleFontSize: 14,
            // String - Tooltip title font weight style
            tooltipTitleFontStyle: "bold",
            // String - Tooltip title font colour
            tooltipTitleFontColor: "#fff",
            // Number - pixel width of padding around tooltip text
            tooltipYPadding: 6,
            // Number - pixel width of padding around tooltip text
            tooltipXPadding: 6,
            // Number - Size of the caret on the tooltip
            tooltipCaretSize: 8,
            // Number - Pixel radius of the tooltip border
            tooltipCornerRadius: 6,
            // Number - Pixel offset from point x to tooltip edge
            tooltipXOffset: 10,
            // String - Template string for single tooltips
            tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>",
            // String - Template string for single tooltips
            multiTooltipTemplate: "<%= value %>",
            // Function - Will fire on animation progression.
            onAnimationProgress: function() {
            },
            // Function - Will fire on animation completion.
            onAnimationComplete: function() {
            }
        }


        var options =
                {
                    //Boolean - If there is a stroke on each bar
                    barShowStroke: false,
                    //Number - Spacing between each of the X value sets
                    barValueSpacing: 0,
                    //Number - Spacing between data sets within X values
                    barDatasetSpacing: 1
                };


        new Chart(document.getElementById("canvas_line").getContext("2d")).Bar(data, options);
        new Chart(document.getElementById("canvas_doughnut1").getContext("2d")).Doughnut(data2, options);
        new Chart(document.getElementById("canvas_doughnut2").getContext("2d")).Doughnut(data3, options);
        new Chart(document.getElementById("canvas_doughnut3").getContext("2d")).Doughnut(data4, options);

    });


</script>

<section id="overview-content">


    <!-- NOTIFICATION AREA -->

    <?php
    if ($logged_in)
    {
        ?>

        <div style="padding:6px 10px; width:100%; height:30px;background: rgba(0,0,0,.3); color:#FFF;text-shadow: 1px 1px 3px #666; ">

            <img src="resources/icons/icons_0002_Calendar-today-small.png" height="15" alt="Info" /> <?php echo date("l d F Y"); ?> 
            <span style="display:inline-block;padding-left:10px"></span> <img src="resources/icons/icons_0023_Clock-small.png" height="15" alt="Info" /> <?php echo date("H:i"); ?>
        </div>

        <div class="main" style="margin-top:70px; ">

            <div class="status-bar">

                <?php
                $archiving_status = $tk->statusLiveArchiving();
                if ($archiving_status[0] == FALSE)
                {
                    echo '<h4 style="display:inline-block"><span class="label label-' . (($archiving_status[2] == 1) ? "danger" : "warning") . '"><img src="resources/icons/icons_0021_Off-small.png" /> Stopped</span></h4>';
                    echo "<div style='display:inline-block' class='" . (($archiving_status[2] == 1) ? "danger" : "warning") . "'>$archiving_status[1] <a href='startarchiving.php'>Start</a></div>";
                } else
                {
                    echo '<h4 style="display:inline-block"><span class="label label-success"><img src="resources/icons/icons_0045_Check-small.png" /> OK</span></h4>';
                    echo "<div style='display:inline-block' class='correct'>$archiving_status[1] </div>";
                }
                ?>
            </div>




            <div class="main-block" style="min-height: 150px;  ">
                <div style="background:url(resources/header-pannel-tail.png) repeat-x; height:30px; padding:5px" >
                    <span style='font-weight:bold; color:#111;'><img src="resources/icons/icons_0054_Bar-Graph-small_grey.png" style='position:relative;top:-1px; left:-1px' /> Statistics</span>                   
                </div>

                <div class='stat'><span class='big'><?php echo number_format($stats["num_tweets"]); ?></span> tweets in total.</div>



                <canvas id="canvas_line" height="150" width="950"></canvas>


                <?php
                if ($archiving_status[0] !== FALSE)
                {
                    ?>
                    <div class='stat'>Currently fetching <span class='big'><?php echo $stats["avg_tweets"]; ?> </span> tweets <span class='big'>per minute</span>.</div>
                    <?php
                }
                ?>

            </div>




            <div class="main-block" style="min-height: 150px; margin:30px 0 0 0">
                <div style="background:url(resources/header-pannel-tail.png) repeat-x; height:30px; padding:5px" >
                    <span style='font-weight:bold; color:#111;'><img src="resources/icons/icons_0020_Looking-Glass-small_grey.png" style='position:relative;top:1px; left:-1px' /> Performance &amp; Health</span>
                    <br/>

                </div>






            </div>
            <div class="main-block" style="min-height: 150px; margin:0 0 50px 0">
                <div style="background:url(resources/header-pannel-tail.png) repeat-x; height:30px; padding:5px" >
                    <span style='font-weight:bold; color:#111;'><img src="resources/icons/icons_0020_Looking-Glass-small_grey.png" style='position:relative;top:1px; left:-1px' /> System Load</span>
                    <br/>

                </div>


                <canvas id="canvas_doughnut1" height="200" width="200" style='margin:25px 40px 25px 90px;'></canvas>

                <canvas id="canvas_doughnut2" height="200" width="200" style='margin:25px 40px;'></canvas>

                <canvas id="canvas_doughnut3" height="200" width="200" style='margin:25px 40px;'></canvas>





            </div>


        </div>







    <?php } ?>


</section>


<?php include("templates/footer.php"); ?>
