<?php
$sub_menu = '400920';
$pg_code = 'kcp';
include_once './_common.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$service_model = new BillingServiceModel();

/** Form Data */
/* 기본정보 */
$post_count_chk = (isset($_POST['chk']) && is_array($_POST['chk'])) ? count($_POST['chk']) : 0;
$chk            = (isset($_POST['chk']) && is_array($_POST['chk'])) ? $_POST['chk'] : array();

for ($i = 0; $i < $post_count_chk; $i++) {
    // 실제 번호를 넘김
    $k = isset($_POST['chk'][$i]) ? (int) $_POST['chk'][$i] : 0;

    $service_id = isset($_POST['service_id'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['service_id'][$k]) : 0;
    $data = array(
        "name"          => isset($_POST['name'][$k]) ? clean_xss_tags($_POST['name'][$k], 1, 1) : '',
        "order"         => isset($_POST['order'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['order'][$k]) : 0,
        "is_use"        => isset($_POST['is_use'][$k]) ? preg_replace('/[^0-9]/', '', $_POST['is_use'][$k]) : 0
    );
    $service_model->update($service_id, $data);
}

goto_url('./service_list.php?' . $qstr);
