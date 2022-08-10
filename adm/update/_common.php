<?php
define('G5_IS_ADMIN', true);
include_once '../../common.php';
include_once G5_ADMIN_PATH . '/admin.lib.php';

// version => github release
define('G5_GNUBOARD_RELEASE', 'v' . G5_GNUBOARD_VER);

// update autoloader
include_once G5_PLUGIN_PATH . '/version_update/Autoloader.php';
$autoloader = new G5UpdateAutoLoader();
$autoloader->register();

if (!isset($g5['update'])) {
    $g5['update'] = new G5Update();
}
