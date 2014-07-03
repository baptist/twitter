<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 30000);

set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*
while (ob_get_level())
    ob_end_clean(); // remove output buffers
ob_implicit_flush(true);

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


        $('#tagsSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            enableFiltering: true,
            maxHeight: 250,
            buttonWidth: 220
        });


        var availableKeywords = [
<?php
$keys = $tk->getKeywords(-1);
foreach ($keys as $key)
    echo "'$key',"
    ?>
        ];

        function split(val) {
            return val.split(/,\s*/);
        }
        function extractLast(term) {
            return split(term).pop();
        }
        $("#keywordsAuto")
// don't navigate away from the field on tab when selecting an item
                .bind("keydown", function(event) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).data("ui-autocomplete").menu.active) {
                event.preventDefault();
            }
        })
                .autocomplete({
            minLength: 0,
            source: function(request, response) {
// delegate back to autocomplete, but extract the last term
                response($.ui.autocomplete.filter(
                        availableKeywords, extractLast(request.term)));
            },
            focus: function() {
// prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
// remove the current input
                terms.pop();
// add the selected item
                terms.push(ui.item.value);
// add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(", ");
                return false;
            }
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

    function displayExport() {
        document.getElementById('export').style.display = 'block';
    }

    function progress(percent) {
        document.getElementById('done').innerHTML = percent + '%';
    }

    function setInformation(num_tweets, num_archives) {
        document.getElementById('information').style.display = 'block';
        document.getElementById('information').innerHTML = "<span style='font-weight:bold'>Number of archives: " + num_archives + "</span> <br/>" +
                "<span style='font-weight:bold'>Number of tweets: " + num_tweets + "</span> <br/>";

        document.getElementById('loader').style.display = 'none';
        document.getElementById('export_btn').style.display = 'block';
    }

</script>

<section id="overview-content">

    <?php
    if ($logged_in)
    {
        ?>

        <form action='export_process.php' target='export_process' method='post'  >

            <div class ="top-title" style="margin:70px 0 0 0; ">
                Select
            </div>

            <div class ="top-title" style="margin:70px 0 0 68%; ">
                Filter
            </div>



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
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>No RT</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>No Mentions</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Include Reactions</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Retweets</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Min Favorites</td>
                                <td class="main-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Limit</td>
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
                                <td><input name='keywords' id='keywordsAuto'/></td>
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
                                <td><input type="checkbox" name='no_mentions' /></td> 
                                <td><input type="checkbox" name='include_reactions' /></td> 
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




        <iframe name="export_process" frameborder="0" scrolling="0" width="1" height="1"></iframe>

        <div class="main" id="export" style="display:none">
            <div class="main-block">
                <span class="main-header">Export archives</span> <br/>

                <div id="loader" style='font-size:120%'>

                    <div style="position:relative; top:-1px;display:inline-block;width:50px; height:50px; margin:10px"><img src='resources/ajax-loader_blue.gif' /></div>
                    <div id="done" style="position:relative;display:inline-block">0% </div>
                </div>

                <div id="information"  style="display:none">                  
                </div>

                <div id="export_btn"  style="display:none; margin-top:25px; padding:10px">               
                    <a href="excel.php?from_table=1" >Export to Excel</a>

                </div>



                <br/><br/>



            </div>


        </div>



    <?php } ?>



</section>

<br/>

<?php include("templates/footer.php"); ?>
