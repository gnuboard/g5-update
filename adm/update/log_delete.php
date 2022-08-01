<?php
$sub_menu = '100600';
require_once './_common.php';

check_admin_token();

$action = $_POST['action'];
$g5UpdateLog = new G5UpdateLog();

if ($action == "list") {
    $logFileList = (isset($_POST['chk']) && is_array($_POST['chk'])) ? $_POST['chk'] : array();

    foreach ($logFileList as $key => $fileName) {
        $g5UpdateLog->deleteLogFile($fileName);
    }
} elseif ($action == "form") {
    $fileName = isset($_POST['filename']) ? clean_xss_tags($_POST['filename']) : "";
    $g5UpdateLog->deleteLogFile($fileName);
}

goto_url("./log.php");
