<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 30000);

set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*
while (ob_get_level())
    ob_end_clean(); // remove output buffers
ob_implicit_flush(true);


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


        window.select_statements = {};
        window.filter_statements = {};
        window.analyze_statements = {};

        window.deleteFromSelectList = function(key)
        {

            delete window.select_statements[key];
            window.refreshSelectList();
        }

        window.deleteFromFilterList = function(key)
        {

            delete window.filter_statements[key];
            window.refreshFilterList();
        }
        
        window.deleteFromAnalyzeList = function(key)
        {

            delete window.analyze_statements[key];
            window.refreshAnalyzeList();
        }

        window.refreshSelectList = function() {
            // display list with select statements
            $("#select_list").empty();

            var list = "";
            var count = 0;
            for (var field in window.select_statements) {
                count++;
                list += "<li class='query-item'>Based on<span style='text-decoration:underline'>" + field + "</span>: " + window.select_statements[field] + " &nbsp;&nbsp;<a href='javascript:deleteFromSelectList(\"" + field + "\");'>delete</a></li>";
            }
            $("#select_list").append(list);

            if (count > 0)
                $(".submit").css("display", "inline-block");
            else
                $(".submit").css("display", "none");
        };

        window.refreshFilterList = function() {
            // display list with select statements
            $("#filter_list").empty();

            var list = "";
            for (var field in window.filter_statements) {
                list += "<li class='filter-item'>Filter on<span style='text-decoration:underline'>" + field + "</span>: " + window.filter_statements[field] + " &nbsp;&nbsp;<a href='javascript:deleteFromFilterList(\"" + field + "\");'>delete</a></li>";
            }
            $("#filter_list").append(list);
        };
        
        window.refreshAnalyzeList = function() {
            // display list with select statements
            $("#analyze_list").empty();

            var list = "";
            for (var field in window.analyze_statements) {
                list += "<li class='analyze-item'>Analyze on<span style='text-decoration:underline'>" + field + "</span>: " + window.analyze_statements[field] + " &nbsp;&nbsp;<a href='javascript:deleteFromAnalyzeList(\"" + field + "\");'>delete</a></li>";
            }
            $("#analyze_list").append(list);
        };
        

        $('button').button();
        $('input[type=submit]').button();

        $(".submit").click(function() {
            var combined = {};
            combined["select"] = window.select_statements;
            combined["filter"] = window.filter_statements;
            combined["analyze"] = window.analyze_statements;

            var check_progress = setInterval(function() {
                $.ajax({method: 'get', url: 'functions/get_progress.php', success: function(data) {
                        if (data !== "")
                            $("#done").text(data);
                    }});
            }, 100);
            $("#result").find("div.top-title").text("Retrieving");
            $("#result").css("display", "block");
            $("#information").parent().css("display", "none");
            $("#done").text("0%");
            $("#loader").css("display", "block");
            $(this).find("button").attr("disabled", "disabled");

            $.ajax({
                method: 'POST',
                url: 'export_process.php',
                data: {data: JSON.stringify(combined)},
                success: function(data) {                    
                    clearInterval(check_progress);
                    $("#result").find("div.top-title").text("Result");
                    $("#loader").css("display", "none");
                    $(".submit").find("button").removeAttr("disabled");
                    $("#information").parent().css("display", "block");
                    $("#information").html(data);

                },
                error: function(xhr, err) {
                    //alert("readyState: " + xhr.readyState + "\nstatus: " + xhr.status);
                    //alert("responseText: " + xhr.responseText);
                    $("#result").find("div.top-title").text("Error");
                },
            });
        });


        $('#select_show').on("click", function() {
            $('#select_widget').bPopup({onOpen: function() {
                    $("#select-widget-combo").trigger("change");
                }});
        });

        $('#filter_show').on("click", function() {
            $('#filter_widget').bPopup({onOpen: function() {
                    $("#filter-widget-combo").trigger("change");
                }});
        });
        
        $('#analyze_show').on("click", function() {
            $('#analyze_widget').bPopup({onOpen: function() {
                    $("#analyze-widget-combo").trigger("change");
                }});
        });




    });



</script>

<section id="overview-content">

    <?php
    if ($logged_in)
    {
        ?>

        <div class="main" style="margin-top:70px; ">
            <div class="main-block" style="min-height: 150px">

                <div style="padding:7px;">

                    <div class="top-title" style="width:250px">Export Archives</div>



                    <a href="#" id="select_show" class='export-btn'>select</a>
                    <a href="#" id="" class='export-btn' style="color:#000">|</a>
                    <a href="#" id="filter_show" class='export-btn'>filter</a>
                    <a href="#" id="" class='export-btn' style="color:#000">|</a>
                    <a href="#" id="analyze_show" class='export-btn'>analyze</a>
                    <!--<a href="#" id="" class='export-btn' style="color:#000">|</a>
                    <a href="#" id="process_show" class='export-btn'>process</a>-->
                    <div style="float:right; margin:10px 10px; display:none" class="submit">
                        <button name="submit" class="ui-button-primary" style="padding-left:5px" value="" >Retrieve</button> 
                    </div>

                </div>

                <div>
                    <ul id="select_list"></ul>
                </div>

                <div>
                    <ul id="filter_list"></ul>
                </div>
                
                <div>
                    <ul id="analyze_list"></ul>
                </div>



            </div>


            <div class="main-block" style="display:none" id ="result">

                <div style="padding:7px;">

                    <div class="top-title" style="width:250px"></div>


                    <div id="loader" style='font-size:120%'>

                        <div style="position:relative; top:-1px;display:inline-block;width:50px; height:50px; margin:10px"><img src='resources/ajax-loader2.gif' /></div>
                        <div style="position:relative;display:inline-block"><span id="done">0%</span> </div>
                    </div>

                    <div  style="display:none;">
                        <div id="information" style="font-size:125%; padding:10px"></div>
                        <div id="export_btn"  style="margin-top:25px; padding:10px">               
                            <a href="excel.php?from_table=1" >Export to Excel</a>

                        </div>
                    </div>


                    <br/><br/>
                </div>


            </div>



        </div>



        <!--
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
        $groupings = array("total", "year", "month", "day", "hour", "user");

        foreach ($groupings as $grouping)
            echo "<option value='$grouping'>" . ucfirst($grouping) . "</option>";
        ?>
        
                                            </select>
                                        </td>      
                                        <td></td> 
                                        <td><input type="checkbox" name='user_stats' /></td> 
        
                                    </tr>
        
                                </table>
        
                                <br/>
                                <br/>
                            </div>
                        </div>
                    </div>
        -->






<?php } ?>



</section>
<div id="select_widget" style="display: none">
    <?php
    include("templates/export_select_widget.php");
    ?>
</div>

<div id="filter_widget" style="display: none">
    <?php
    include("templates/export_filter_widget.php");
    ?>
</div>

<div id="analyze_widget" style="display: none">
    <?php
    include("templates/export_analyze_widget.php");
    ?>
</div>

<br/>

<?php include("templates/footer.php"); ?>
