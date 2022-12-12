<?php
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}
/* Database Table 선언 */
$g5['billing_information_table']        = G5_TABLE_PREFIX . 'billing_information';
$g5['billing_history_table']            = G5_TABLE_PREFIX . 'billing_history';
$g5['billing_service_table']            = G5_TABLE_PREFIX . 'billing_service';
$g5['billing_service_price_table']      = G5_TABLE_PREFIX . 'billing_service_price';
$g5["billing_key_history_table"]        = G5_TABLE_PREFIX . "billing_key_history";
$g5["billing_cancel_table"]             = G5_TABLE_PREFIX . "billing_cancel";
$g5['billing_scheduler_history_table']  = G5_TABLE_PREFIX . "billing_scheduler_history";
$g5['billing_config_table']             = G5_TABLE_PREFIX . "billing_config";

/* Autoloader */
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

/* 자동결제 설정 조회 */
$config_model = new BillingConfigModel();
$billing_conf = $config_model->selectOne();

/* 상수 선언 */
// 인증서 경로
define('kcp_cert_path', G5_DATA_PATH . "/billing/kcp/certificate/");
// 사이트코드
define('site_cd', (isset($billing_conf['bc_kcp_site_cd']) && !empty($billing_conf['bc_kcp_site_cd'])) ? $billing_conf['bc_kcp_site_cd'] : '');
// 그룹 ID
define('kcpgroup_id', (isset($billing_conf['bc_kcp_group_id']) && !empty($billing_conf['bc_kcp_group_id'])) ? $billing_conf['bc_kcp_group_id'] : '');
// Priavate Key Password
define('PRIVATE_PW', (isset($billing_conf['bc_kcp_prikey_password']) && !empty($billing_conf['bc_kcp_prikey_password'])) ? $billing_conf['bc_kcp_prikey_password'] : '');
