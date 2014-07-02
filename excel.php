<?php

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 30000);

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

// Load important files
session_start();
require_once('config.php');
require_once('function.php');
require_once('twitteroauth.php');

if (isset($_SESSION['export_from_table']) || isset($_GET['from_table']))
{
    $data = $tk->getExportData();
    $keys_to_print = array_keys(current($data));

} else
{
    $keys_to_print = array("text",
        "to_user_id",
        "to_user",
        "from_user_id",
        "from_user",
        "original_user_id",
        "original_user",
        "id",
        //"in_reply_to_status_id",
        "iso_language_code",
        "profile_image_url",
        "geo_type",
        "geo_coordinates_0",
        "geo_coordinates_1",
        "created_at",
        "time",
        "favorites",
        "retweets",
        "description",
        "tags"
    );


    if (!isset($_SESSION['tweets']))
    {
        if (isset($_GET['id']))
        {
            $id = $_GET['id'];
            $archiveInfo = $tk->listArchive($id);


            if ($archiveInfo['count'] <> 1 && isset($_GET['id']))
            {
                $_SESSION['notice'] = "Archive does not exist.";
                header('Location: index.php');
            }
        }
        else
            $archiveInfo = $tk->listArchivesWithCondition("1");

        // set default limit
        if ($_GET['l'] == '')
        {
            $limit = 10;
        } else
        {
            $limit = $_GET['l'];
        }
        if ($_GET['o'] == '')
        {
            $orderby = 'd';
        } else
        {
            $orderby = $_GET['o'];
        }

// set times
        if ($_GET['sm'] <> '' && $_GET['sd'] <> '' && $_GET['sy'] <> '')
        {
            $start_time = strtotime($_GET['sm'] . "/" . $_GET['sd'] . "/" . $_GET['sy']);
        }
        if ($_GET['em'] <> '' && $_GET['ed'] <> '' && $_GET['ey'] <> '')
        {
            $end_time = strtotime($_GET['em'] . "/" . $_GET['ed'] . "/" . $_GET['ey']);
        }

// Get tweets
        if ($start_time <> '' || $end_time <> '')
        {
            $data = $tk->getTweetsFromArchives($archiveInfo['results'], $start_time, $end_time, $limit, $orderby, $_GET['nort'], $_GET['from_user'], $_GET['text'], $_GET['lang'], $_GET['max_id'], $_GET['since_id'], $_GET['offset'], $_GET['lat'], $_GET['long'], $_GET['rad'], $_GET['debug'], 1, 1);
        } else
        {
            $data = $tk->getTweetsFromArchives($archiveInfo['results'], null, null, $limit, $orderby, $_GET['nort'], $_GET['from_user'], $_GET['text'], $_GET['lang'], $_GET['max_id'], $_GET['since_id'], $_GET['offset'], $_GET['lat'], $_GET['long'], $_GET['rad'], $_GET['debug'], 1, 1);
        }
    }
    else
        $data = $_SESSION['tweets'];
}

// set link
$link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


$export_file = "export_" . date("d_m_y") . ".xls";
ob_end_clean();
ini_set('zlib.output_compression', 'Off');

header('Pragma: public');
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");                  // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Transfer-Encoding: none');
header('Content-Type: application/vnd.ms-excel; charset=utf-8');                 // This should work for IE & Opera
header("Content-type: application/x-msexcel; charset=utf-8");                    // This should work for the rest
header('Content-Disposition: attachment; filename="' . basename($export_file) . '"');
/*
  echo "<head>";
  echo "<meta http-equiv=\"content-type\" content=\"text/html;charset=utf-8\" />";
  echo "</head>"; 
*/
echo "<table>";
echo "<tr>";

foreach ($keys_to_print as $key)
    echo "<th>" . strtoupper($key) . "</th>";
echo "</tr>";

$ids = array();
foreach ($data as $key => $value)
{   
    if ($value == NULL || empty($value))
        continue;
        
    if (!array_key_exists('id', $value) || (array_key_exists('id', $value) && !in_array($value['id'], $ids)))
    {
        echo "<tr>";
        foreach ($keys_to_print as $print)
        {
            if ($print == "id" || $print == "original_id")
                echo "<td>'" . $value['id'] . "'</td>";
            else
                echo "<td>" . $value[$print] . "</td>";
        }


        if (array_key_exists('text', $value))
        {
            // Check if tweet contains url
            preg_match_all('/https?:\/\/[^\s`!()\[\]{};:\'\",<>?«»“”‘’]+/', $value["text"], $urls);

            if ($urls !== NULL && count($urls[0]) > 0)
            {
                foreach ($urls[0] as $url)
                {
                    $expandedURL = $tk->expandShortUrl($url);
                    echo "<td>$url</td><td>$expandedURL</td>";
                }
            }
        }
        echo "</tr>";
        
        if (array_key_exists('id', $value))
            $ids[] = $value['id'];
    }
}

echo "</table>";
?>
