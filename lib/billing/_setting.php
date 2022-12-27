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
define('site_cd', !empty($billing_conf['bc_kcp_site_cd']) ? $billing_conf['bc_kcp_site_cd'] : '');
// 그룹 ID
define('kcpgroup_id', !empty($billing_conf['bc_kcp_group_id']) ? $billing_conf['bc_kcp_group_id'] : '');
// Priavate Key Password
define('PRIVATE_PW', !empty($billing_conf['bc_kcp_prikey_password']) ? $billing_conf['bc_kcp_prikey_password'] : '');

/**
 * @param mixed $s
 * @return mixed|false
 */
function han($s)
{
    return reset(json_decode('{"s":"' . $s . '"}'));
}

/**
 * PHP 5.3 이하에서 json_encode JSON_UNESCAPED_UNICODE 구현
 * @param $str
 * @return string|string[]|null
 */
function to_han($str)
{
    return preg_replace('/(\\\u[a-f0-9]+)+/e', 'han("$0")', $str);
}

/**
 * json 형식으로 메시지를 출력 후 exit 합니다.
 * @param array|string $data
 * @param string $http_status_code
 * @return void
 */
function response_json($data, $http_status_code = 200)
{
    header('Content-type: application/json; charset=utf-8', true, $http_status_code);

    if (is_string($data)) {
        $resData['result_message'] = $data;
    } else {
        $resData = $data;
    }

    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($resData, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($resData));
    }

    exit;
}
