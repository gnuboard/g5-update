<?php
$sub_menu = '100600';
require_once './_common.php';

check_admin_token();


$backupFileList = (isset($_POST['chk']) && is_array($_POST['chk'])) ? $_POST['chk'] : array();

foreach ($backupFileList as $key => $fileName) {
    $backupfile = $g5['update']->dir_backup . "/" . $fileName;
    if (!unlink($backupfile)) {
        alert("파일삭제가 실패했습니다.\\n 파일명 : " . $backupfile, "./rollback.php");
    }
}

goto_url("./rollback.php");
