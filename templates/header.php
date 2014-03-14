<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <title>Your Twapper Keeper 2.0</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <link href="css/custom-theme/jquery-ui-1.8.4.custom.css" rel="stylesheet" type="text/css">
            <link href="css/yourtwapperkeeper.css?v=2" rel="stylesheet" type="text/css">
                <script src="js/jquery-1.4.2.min.js"></script>
                <script src="js/jquery-ui-1.8.4.custom.min.js"></script>
                </head>

                <div class="header-panel-top">
                    <div class="main">
                        <div class="header-inner">
                            <?php
                            if (!$logged_in) {
                                ?>
                                <ul class="menu1">
                                    <li class="item-1"><span><a href="">Login</a></span></li>
                                    <li class="item-2"><a href="">Add User</a></li>
                                </ul>
                            <?php } else { ?>
                                <div class="info">
                                    Logged in as <span class="special"><?php echo $_SESSION['access_token']['screen_name'] ?></span> &nbsp; / &nbsp; <a href=""> Logout</a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="clear">
                        </div>
                    </div>
                </div>

                <div class="header-panel">               
                    <div class="header-main">

                        <div class="header-title">
                            <div class ="title"></div>                        
                        </div>
                        <?php
                        if ($logged_in) {
                            ?>

                            <div class="buttons-pos">
                                <ul class="sf-menu sf-js-enabled">
                                    
                                        <li><a href="">Overzicht</a></li>  
                                   
                                        <li><a href="">Werk</a></li>  
                                  
                                        <li><a href="">Financi&euml;n</a></li>  
                                 
                                        <li><a href="">Statistieken</a></li>  
                                  
                                        <li><a href="">Instellingen</a></li>  
                                </ul>
                            </div>

                        <?php } ?>
                    </div>
                </div>
