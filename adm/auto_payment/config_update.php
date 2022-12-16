<?php
$sub_menu = '800940';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

/* 변수 선언 */
$config_model  = new BillingConfigModel();

$bc_kcp_cert   = isset($_POST['bc_kcp_cert']) ? $_POST['bc_kcp_cert'] : null;
$bc_kcp_prikey = isset($_POST['bc_kcp_prikey']) ? $_POST['bc_kcp_prikey'] : null;

/* kcp 인증서 경로 체크 & 생성 */
if ($_POST['bc_pg_code'] == 'kcp') {
    if (!is_dir(kcp_cert_path)) {
        @mkdir(kcp_cert_path, G5_DIR_PERMISSION, true);
        @chmod(kcp_cert_path, G5_DIR_PERMISSION);
    }

    /* 인증서 & 개인키 파일처리 */
    // if (!empty($_POST['bc_kcp_cert_del'])) {
    //     @unlink(G5_PATH . $billing_conf['bc_kcp_cert']);
    // }
    // if (!empty($_POST['bc_kcp_prikey_del'])) {
    //     @unlink(G5_PATH . $billing_conf['bc_kcp_prikey']);
    // }
    if ($_FILES['bc_kcp_cert_file']['name']) {
        $result = upload_file($_FILES['bc_kcp_cert_file']['tmp_name'], $_FILES['bc_kcp_cert_file']['name'], kcp_cert_path);
        if ($result) {
            $bc_kcp_cert = $_FILES['bc_kcp_cert_file']['name'];
        }
    }
    if ($_FILES['bc_kcp_prikey_file']['name']) {
        $result = upload_file($_FILES['bc_kcp_prikey_file']['tmp_name'], $_FILES['bc_kcp_prikey_file']['name'], kcp_cert_path);
        if ($result) {
            $bc_kcp_prikey = $_FILES['bc_kcp_prikey_file']['name'];
        }
    }
}

/* 데이터베이스 입력 */
$data   = array(
    'bc_use_cancel_refund'  => isset($_POST['bc_use_cancel_refund']) ? preg_replace('/[^0-9]/', '', $_POST['bc_use_cancel_refund']) : 0,
    'bc_use_pause'          => isset($_POST['bc_use_pause']) ? preg_replace('/[^0-9]/', '', $_POST['bc_use_pause']) : 0,
    'bc_pg_code'            => isset($_POST['bc_pg_code']) ? $_POST['bc_pg_code'] : '',
    'bc_kcp_site_cd'        => isset($_POST['bc_kcp_site_cd']) ? $_POST['bc_kcp_site_cd'] : null,
    'bc_kcp_group_id'       => isset($_POST['bc_kcp_group_id']) ? $_POST['bc_kcp_group_id'] : null,
    'bc_kcp_cert'           => $bc_kcp_cert,
    'bc_kcp_prikey'         => $bc_kcp_prikey,
    'bc_kcp_prikey_password' => isset($_POST['bc_kcp_prikey_password']) ? $_POST['bc_kcp_prikey_password'] : null,
    'bc_kcp_is_test'        => isset($_POST['bc_kcp_is_test']) ? preg_replace('/[^0-9]/', '', $_POST['bc_kcp_is_test']) : 0,
    'bc_kcp_curruncy'       => isset($_POST['bc_kcp_curruncy']) ? $_POST['bc_kcp_curruncy'] : 410,
    'bc_toss_client_key'    => isset($_POST['bc_toss_client_key']) ? $_POST['bc_toss_client_key'] : null,
    'bc_toss_secret_key'    => isset($_POST['bc_toss_secret_key']) ? $_POST['bc_toss_secret_key'] : null,
    'bc_toss_customer_key'  => isset($_POST['bc_toss_customer_key']) ? $_POST['bc_toss_customer_key'] : null,
    'bc_notice_email'       => isset($_POST['bc_notice_email']) ? $_POST['bc_notice_email'] : null,
    'bc_update_ip'          => $_SERVER['REMOTE_ADDR'],
    'bc_update_id'          => $member['mb_id'],
    'bc_update_time'        => date('Y-m-d H:i:s'),
);

$config_model->insert($data);

goto_url("./config_form.php");