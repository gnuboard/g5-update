<?php
define('G5_IS_ADMIN', true);
include_once '../../common.php';
include_once G5_ADMIN_PATH . '/admin.lib.php';
include_once G5_PLUGIN_PATH . '/version_update/update.lib.php';
include_once G5_PLUGIN_PATH . '/version_update/database.update.lib.php';
include_once G5_PLUGIN_PATH . '/version_update/setup.lib.php';
include_once G5_PLUGIN_PATH . '/version_update/version.lib.php';
include_once G5_PLUGIN_PATH . '/version_update/github.api.lib.php';

if (!isset($g5['update'])) {
    $g5['update'] = new G5Update(G5_PATH);
}
