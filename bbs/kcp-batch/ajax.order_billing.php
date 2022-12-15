<?php
//결제 요청 처리.
require_once dirname(__FILE__) . '/_common.php';

$pg_code = 'kcp';
require_once G5_LIB_PATH . "/billing/G5AutoLoader.php";
require_once G5_BBS_PATH . '/subscription/subscription_service.php';
/* ============================================================================== */
/* =  결제 요청정보 준비                                                           = */
/* = -------------------------------------------------------------------------- = */

define('WON', '410'); // 원화

$billing = new Billing($pg_code);
$information_model  = new BillingInformationModel();
$history_model      = new BillingHistoryModel();
$cancel_model       = new BillingCancelModel();

$unit_array = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day');
//필수 파라미터

$od_id       = isset($_POST['od_id']) ? clean_xss_tags($_POST['od_id']) : '';
$billing_key = isset($_POST['billing_key']) ? $billing_key : '';  // 배치키 정보
$currency    = isset($_POST['currency']) ? $_POST['currency'] : WON;  // 화폐단위
$od_id       = isset($_POST['od_id']) ? $_POST['od_id'] : '';  // 주문 정보
$service_id  = isset($service_id) ?  $service_id : '';  // 구독 서비스 ID
if (empty($od_id) || $service_id === '' || empty($billing_key)) {
    responseJson('필수 파라미터가 없습니다.', 400);
}
$service_info = get_service_detail($service_id);
if (!is_array($service_info) || empty($service_info)) {
    responseJson('구독 정보를 가져오는데 실패했습니다.', 400);
}
$amount = $service_info['price'];  // 결제금액
/**
 * 권장 파라미터
 * @var string $good_name (100byte 이내 약 33글자) 상품명
 *
 */
$service_name = utf8_strcut($service_info['name'], 33, ''); // 상품명

//선택 파라미터
$mb_name = isset($_POST['buyr_name']) ? $buyr_name : '';
$mb_email = isset($_POST['buyr_mail']) ? $buyr_mail : '';
$mb_hp = isset($_POST['buyr_tel2']) ? $buyr_tel2 : '';
$customer_ip = ''; //TODO

$recurring = $service_info['recurring'];  // 정기 결제의 주기 몇일, 몇개월, 몇년 등.
$recurring_unit = $service_info['recurring_unit']; // 정기결제 주기단위

/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = false;

$reqest_billing_data = array(
    'cust_ip' => $customer_ip,
    'amount' => $amount,
    'currency' => $currency,
    'od_id' => $od_id,
    'name' => $service_name,
    'mb_name' => $mb_name,
    'mb_email' => $mb_email,
    'mb_hp' => $mb_hp,
    'billing_key' => $billing_key
);

/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$res_data = $billing->pg->requestBilling($reqest_billing_data);
$res_data = $billing->convertPgDataToCommonData($res_data);
if (isset($res_data['http_code'])) {
    //error 응답.
    responseJson($res_data['result_message'], $res_data['http_code']);
}

if ($res_data['result_code'] !== "0000") {
    responseJson($res_data['result_message'], 400);
}
/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */


/* ============================================================================== */
/* =  응답정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
// 결제일, 구독만료일
$res_data['payment_date'] = date('Y-m-d');
$res_data['expiration_date'] = date(
    'Y-m-d 23:59:59 ',
    strtotime('+' . $service_info['recurring'] . " " . $unit_array[$service_info['recurring_unit']])
);

$payment_insert_data = $billing->convertPgDataToCommonData($res_data);
$result_code = $payment_insert_data['result_code'];
$result_message = $payment_insert_data['result_message'];

if ($result_code === '0000') {
    if ($service_info['price'] === $payment_insert_data['amount']) { //결제된 금액과 서비스 금액 같은지 확인.
        $bSucc = true;
    }
} else {
    responseJson('결제 승인이 실패했습니다.', 200);
}

/* ==============================================================================
 결제 결과처리
 자동결제 정보 저장

==========================================================================
승인 결과 DB 처리 실패시 : 자동취소
--------------------------------------------------------------------------
승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해
DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로
승인 취소 요청을 하는 프로세스가 구성되어 있습니다.

DB 작업이 실패 한 경우, bSucc 라는 변수의 값을 false로 설정해 주시기 바랍니다.
(DB 작업 성공의 경우에는 bSucc 는 true 입니다. );
--------------------------------------------------------------------------
*/
$start_date = date('Y-m-d H:i:s');
if (empty($service_info['expiration'])) {
    $end_date = '0000-00-00 00:00:00';//구독 만료기간이 정해지지않음.
} else {
    $end_date = $billing->nextPaymentDate($start_date, $start_date, $service_info['expiration'], $service_info['expiration_unit']);
    $payment_insert_data['price'] = $payment_insert_data['amount'];
}

$next_payment_date = $billing->nextPaymentDate($start_date, $start_date, $recurring, $recurring_unit);

if ($next_payment_date !== false) {
    $payment_insert_data['mb_id'] = get_user_id();
    $payment_insert_data['service_id'] = $service_id;
    $payment_insert_data['billing_key'] = $billing_key;
    $payment_insert_data['start_date'] = $start_date;
    $payment_insert_data['end_date'] = $end_date;
    $payment_insert_data['next_payment_date'] = $next_payment_date;

    $is_insert = $information_model->insert($payment_insert_data);
    $bSucc = $is_insert;
} else {
    $bSucc = false;
}

// 자동결제 이력 저장
$payment_insert_data['payment_count'] = 0; //첫결제
$history_model->insert($payment_insert_data);

//0000 은 결제성공
if ($result_code === '0000') {
    if ($bSucc === false) {
        payment_cancel($payment_insert_data['payment_no'], $billing);
    }
} else {
    payment_cancel($payment_insert_data['payment_no'], $billing);
}

// 나머지 결과 출력
$res_data = array();
$res_data['result_code'] = $result_code;
$res_data['result_message'] = $result_message;

if (PHP_VERSION_ID >= 50400) {
    echo json_encode($res_data, JSON_UNESCAPED_UNICODE);
} else {
    echo to_han(json_encode($res_data));
}

/**
 * 결제 취소 요청후 종료
 * @param $payment_no
 * @param Billing $billing
 * @return void
 */
function payment_cancel($payment_no, $billing)
{
    // API RES
    $cancle_res = $billing->pg->requestCancelBilling($payment_no);

    // 유효성 검사.
    if (isset($cancle_res['result_code']) && $cancle_res['result_code'] !== '0000') {
        $msg = '결제 취소가 실패했습니다. 관리자 문의바랍니다.';
        responseJson($msg, 401);
    }

    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($cancle_res, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($cancle_res));
    }

    exit;
}