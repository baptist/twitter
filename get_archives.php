<?php
require_once('config.php');
require_once('function.php');


if (isset($_POST['filter']) && $_POST['filter'] !== "" )
{
    $filter = $_POST['filter'];
    if ($filter == "Top 50")
        $archives = $tk->listArchive();
    else
    {
        $archives = $tk->listArchive(false, false, false , $filter);
        if (count($archives) == 1)
            $archives = $tk->listArchive(false, $filter);
    }    
} 
else if (empty($archives))
    $archives = $tk->listArchive();

// list table of archives

echo "<table class='archive'>";
echo "<tr><th style='text-align:left'>Type</th><th style='text-align:left'>Keyword</th><th>Description</th><th>Tags</th><th>Created By</th><th>Count</th><th>Create Time</th><th></th></tr>";

if (array_key_exists("results", $archives))
{

    foreach ($archives['results'] as $value)
    {
        $type = $value['type'];
        $image_type_url = (($type == 1) ? "twitter-icon.png" : (($type == 2) ? "hashtag.png" : (($type == 3) ? "follow.png" : "mention.png")));
        
        echo "<tr><td style='text-align:left'> <img src='resources/$image_type_url' alt='' width='20px' style='border-radius:10px;position:relative;top:2px;margin-right:3px' /> </td><td style='text-align:left'>" . ucfirst(strtolower($value['keyword'])) . "</td><td>" . $value['description'] . "</td><td>" . $value['tags'] . "</td><td>" . $value['screen_name'] . "</td><td style='font-size:120%'>" .number_format ( $value['count']) . "</td><td>" . date("d M Y", $value['create_time']) . "</td>";
        echo "<td>";
        echo "<a href='archive.php?id=" . $value['id'] . "' target='_blank' alt='View'><span class='ui-icon ui-icon-search'  style='display:inline-block'></span></a>";
        //if (isset($_SESSION['access_token']['screen_name']) && $_SESSION['access_token']['screen_name'] == $value['screen_name'])
        //{
            ?>
            <script type="text/javascript">
                $(function() {
                    $("#deletedialog<?php echo $value['id']; ?>").dialog({
                        autoOpen: false,
                        height: 150,
                        width: 800,
                        modal: true
                    });

                    $("#nonactivedialog<?php echo $value['id']; ?>").dialog({
                        autoOpen: false,
                        height: 150,
                        width: 600,
                        modal: true
                    });
                    
                    $('#deletelink<?php echo $value['id']; ?>').click(function() {
                        $('#deletedialog<?php echo $value['id']; ?>').dialog('open');
                        return false;
                    });

                    $("#updatedialog<?php echo $value['id']; ?>").dialog({
                        autoOpen: false,
                        height: 300,
                        width: 300,
                        modal: true
                    });

                    $('#updatelink<?php echo $value['id']; ?>').click(function() {
                        $('#updatedialog<?php echo $value['id']; ?>').dialog('open');
                        return false;
                    });
                    
                    $('#nonactivelink<?php echo $value['id']; ?>').click(function() {
                        $('#nonactivedialog<?php echo $value['id']; ?>').dialog('open');
                        return false;
                    });


                });
            </script>

            <div id = 'deletedialog<?php echo $value['id']; ?>' title='Are you sure you want to delete <?php echo $value['keyword']; ?> archive?'>
                <br><br><center><form method='post' action='delete.php'><input type='hidden' name='id' value='<?php echo $value['id']; ?>'/><input type='submit' value='Yes'/></form></center>
            </div> 
            
            <div id = 'nonactivedialog<?php echo $value['id']; ?>' title='Are you sure you want to deactivate <?php echo $value['keyword']; ?> archive?'>
                <br><br><center><form method='post' action='deactivate.php'><input type='hidden' name='id' value='<?php echo $value['id']; ?>'/><input type='submit' value='Yes'/></form></center>
            </div> 

            <div id = 'updatedialog<?php echo $value['id']; ?>' title='Update <?php echo ($value['keyword']); ?> archive'>
                <br><br><center><form method='post' action='update.php'>Description<br><input name='description' value='<?php echo $value['description']; ?>'/><br><br>Tags<br><input name='tags' value='<?php echo $value['tags']; ?>'/><input type='hidden' name='id' value='<?php echo $value['id']; ?>'/><br><br><p><input type='submit' value='Update'/></p></form></center>
            </div> 
            <?php
            echo "<a href='#' id='updatelink" . $value['id'] . "'><span class='ui-icon 	 ui-icon-pencil' style='display:inline-block'></span></a>";
            echo "<a href='#' id='nonactivelink" . $value['id'] . "'><span class='ui-icon ui-icon-power' style='display:inline-block'></span></a>";            
            echo "<a href='#' id='deletelink" . $value['id'] . "'><span class='ui-icon 	ui-icon-trash' style='display:inline-block'></span></a>";
        //}

        echo "</td>";
        echo "</tr>";
    }
}
echo "</table>";
?>
