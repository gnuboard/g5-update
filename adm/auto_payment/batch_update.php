<?php
$sub_menu = '400920';
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

auth_check_menu($auth, $sub_menu, "w");
check_admin_token();

$g5Mysqli = new G5Mysqli();

$status = isset($_POST['status']) ? preg_replace('/[^0-9]/', '', $_POST['status']) : 0;

$sql = "UPDATE {$g5['batch_info_table']} SET status = ? WHERE od_id = ?";
$g5Mysqli->execSQL($sql, array($status, $od_id), true);

goto_url("./batch_form.php?w=u&amp;od_id={$od_id}");