<?php
session_start();
require_once('../config.php');
require_once('../function.php');

echo $tk->getProgress();
?>