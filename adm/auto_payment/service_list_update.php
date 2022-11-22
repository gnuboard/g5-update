<?php
$sub_menu = '400920';
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$g5Mysqli = new G5Mysqli();

/** Form Data */
/* 기본정보 */
$post_count_chk = (isset($_POST['chk']) && is_array($_POST['chk'])) ? count($_POST['chk']) : 0;
$chk            = (isset($_POST['chk']) && is_array($_POST['chk'])) ? $_POST['chk'] : array();

for ($i = 0; $i < $post_count_chk; $i++) {
    // 실제 번호를 넘김
    $k      = isset($_POST['chk'][$i]) ? (int) $_POST['chk'][$i] : 0;

    $bind_param = array();
    $id     = isset($_POST['service_id'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['service_id'][$k]) : 0;
    $name   = isset($_POST['service_name'][$k]) ? clean_xss_tags($_POST['service_name'][$k], 1, 1) : '';
    $order  = isset($_POST['service_order'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['service_order'][$k]) : 0;
    $use    = isset($_POST['service_use'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['service_use'][$k]) : 0;
    array_push($bind_param, $name, $order, $use, $id);

    $sql = "UPDATE 
                {$g5['batch_service_table']}
            SET
                service_name    = ?,
                service_order   = ?,
                service_use     = ?
            WHERE service_id = ?";
    $g5Mysqli->execSQL($sql, $bind_param, true);
}

goto_url('./service_list.php?' . $qstr);
