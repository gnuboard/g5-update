<?php
include_once "./_common.php";

$username       = isset($_POST['username']) ? clean_xss_tags($_POST['username'], 1, 1) : null;
$userPassword   = isset($_POST['password']) ? clean_xss_tags($_POST['password'], 1, 1) : null;
$port           = isset($_POST['port']) ? clean_xss_tags($_POST['port'], 1, 1) : null;

$g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $userPassword);
