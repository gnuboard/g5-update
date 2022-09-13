<?php
define('G5_IS_ADMIN', true);
include_once '../../common.php';
include_once G5_ADMIN_PATH . '/admin.lib.php';

// version => github release
$gnu_version = (version_compare('5.4.6', G5_GNUBOARD_VER, "<") ? 'v' : ''). G5_GNUBOARD_VER;
define('G5_GNUBOARD_RELEASE', $gnu_version);

// update autoloader
include_once G5_PLUGIN_PATH . '/version_update/Autoloader.php';
$autoloader = new G5UpdateAutoLoader();
$autoloader->register();

if (!isset($g5['update'])) {
    $g5['update'] = new G5Update();
}
