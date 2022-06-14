<?php
define('G5_IS_ADMIN', true);
include_once '../../common.php';
include_once './update.lib.php';
include_once './update.lib.test.php';
include_once G5_ADMIN_PATH . '/admin.lib.php';

if (!isset($g5['update'])) {
    $g5['update'] = new G5Update(G5_PATH);
    $g5['update']->setNowVersion("v" . G5_GNUBOARD_VER);
}
if (!isset($g5['update_test'])) {
    $g5['update_test'] = new G5UpdateTest();
}

