<?php
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

        $("input[type=submit],button").button();
    });

</script>

<section id="overview-content">


   
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
                         style="margin-right:6px;position:relative; top:-3px"/>
                    <input type="text" name="focus" id="focus" value="" style="position:relative;top:-3px;width:150px;height:25px" />
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
