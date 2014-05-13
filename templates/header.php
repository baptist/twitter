<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <title>Your Twapper Keeper 2.0</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />       
        
        <link rel="stylesheet" href="css/bootstrap-3.1.1.min.css"/>
        <link rel="stylesheet" href="css/bootstrap-multiselect.css"/>
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css"/>
        <link href="css/yourtwapperkeeper.css?v=2" rel="stylesheet" type="text/css" />
        <script src="js/jquery-2.1.0.min.js"></script>
        <script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
        <script src="js/bootstrap-3.1.1.min.js"></script>
        <script src="js/bootstrap-multiselect.js"></script>
        <script src="js/Chart.js"></script>
        <style>
            canvas{
            }
        </style>
    </head>
    <body>

        <section>

            <div class="header-panel-top">




                <div class="header-inner">
                    <?php
                    if (!$logged_in)
                    {
                        ?>
                        <div class="info">
                            <a href='./oauthlogin.php'>Login</a>
                        </div>
                    <?php } else
                    {
                        ?>
                        <div class="info">
                            Logged in as <span class="special"><?php echo $_SESSION['access_token']['screen_name'] ?></span> &nbsp; / &nbsp; <a href="clearsessions.php"> Logout</a>
                        </div>
<?php } ?>
                </div>
                

            </div>


            <div class="header-panel">               
                <div class="header-main">

                    <div class="header-title">
                        <div class ="title"></div>                        
                    </div>

                    <?php
                    if ($logged_in)
                    {
                        ?>

                        <div class="buttons-pos">
                            <ul class="sf-menu sf-js-enabled">

                                <?php if (basename($_SERVER['PHP_SELF']) === "index.php")
                                {
                                    ?>
                                    <li class=""><a class="current" href="#">Overview</a></li>
                                <?php } else
                                {
                                    ?>
                                    <li><a href="index.php">Overview</a></li>  
                                <?php } ?>

                                <?php if (basename($_SERVER['PHP_SELF']) === "multiple_archives.php")
                                {
                                    ?>
                                    <li class=""><a class="current" href="#">Archives</a></li>
    <?php } else
    {
        ?>
                                    <li><a href="multiple_archives.php">Archives</a></li>  
                                <?php } ?>


                                <?php if (basename($_SERVER['PHP_SELF']) === "export.php")
                                {
                                    ?>
                                    <li class=""><a class="current" href="#">Export</a></li>
                                <?php } else
                                {
                                    ?>
                                    <li><a href="export.php">Export</a></li>  
                                <?php } ?>

    <?php if (basename($_SERVER['PHP_SELF']) === "settings.php")
    {
        ?>
                                    <li class=""><a class="current" href="#">Settings</a></li>
                        <?php } else
                        {
                            ?>
                                    <li><a href="settings.php">Settings</a></li>  
    <?php } ?>


                            </ul>
                        </div>

<?php } ?>



                </div>

            </div>

        </section>
        <div class="clear"></div>
