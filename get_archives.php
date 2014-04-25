<?php
require_once('config.php');
require_once('function.php');

$filter = $_POST['filter'];



if (isset($filter) && $filter !== "" )
{
    if ($filter == "Top 50")
        $archives = $tk->listArchive();
    else
    {
        $archives = $tk->listArchive(false, false, false , $filter);
        if (count($archives) == 1)
            $archives = $tk->listArchive(false, $filter);
    }    
}
    
else
    $archives = $tk->listArchive();

// list table of archives

echo "<table>";
echo "<tr><th>Archive ID</th><th>Keyword / Hashtag</th><th>Description</th><th>Tags</th><th>Screen Name</th><th>Count</th><th>Create Time</th><th></th></tr>";

if (array_key_exists("results", $archives))
{

    foreach ($archives['results'] as $value)
    {
        echo "<tr><td>" . $value['id'] . "</td><td>" . $value['keyword'] . "</td><td>" . $value['description'] . "</td><td>" . $value['tags'] . "</td><td>" . $value['screen_name'] . "</td><td>" . $value['count'] . "</td><td>" . date(DATE_RFC2822, $value['create_time']) . "</td>";
        echo "<td>";
        echo "<a href='archive.php?id=" . $value['id'] . "' target='_blank' alt='View'><img src='./resources/binoculars_24.png' alt='View Archive' title='View Archive'/></a>";
        if (isset($_SESSION['access_token']['screen_name']) && $_SESSION['access_token']['screen_name'] == $value['screen_name'])
        {
            ?>
            <script type="text/javascript">
                $(function() {
                    $("#deletedialog<?php echo $value['id']; ?>").dialog({
                        autoOpen: false,
                        height: 150,
                        width: 800,
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


                });
            </script>

            <div id = 'deletedialog<?php echo $value['id']; ?>' title='Are you sure you want to delete <?php echo $value['keyword']; ?> archive?'>
                <br><br><center><form method='post' action='delete.php'><input type='hidden' name='id' value='<?php echo $value['id']; ?>'/><input type='submit' value='Yes'/></form></center>
            </div> 

            <div id = 'updatedialog<?php echo $value['id']; ?>' title='Update <?php echo $value['keyword']; ?> archive'>
                <br><br><center><form method='post' action='update.php'>Description<br><input name='description' value='<?php echo $value['description']; ?>'/><br><br>Tags<br><input name='tags' value='<?php echo $value['tags']; ?>'/><input type='hidden' name='id' value='<?php echo $value['id']; ?>'/><br><br><p><input type='submit' value='Update'/></p></form></center>
            </div> 
            <?php
            echo "<a href='#' id='updatelink" . $value['id'] . "'><img src='./resources/pencil_24.png' alt='Edit Archive' title='Edit Archive'/></a>";
            echo "  <a href='#' id='deletelink" . $value['id'] . "'><img src='./resources/close_2_24.png' alt='Delete Archive' title='Delete Archive'/></a>";
        }

        echo "</td>";
        echo "</tr>";
    }
}
echo "</table>";
?>
