<?php
//결제 요청 처리.
require_once dirname(__FILE__) . '/_common.php';
require_once G5_BBS_PATH . '/subscription/subscription_service.php';
/* ============================================================================== */
/* =  결제 요청정보 준비                                                           = */
/* = -------------------------------------------------------------------------- = */
$information_model  = new BillingInformationModel();
$history_model      = new BillingHistoryModel();
$cancel_model       = new BillingCancelModel();

//필수 파라미터
$od_id       = isset($_POST['od_id']) ? clean_xss_tags($_POST['od_id']) : '';
$billing_key = isset($_POST['billing_key']) ? $billing_key : '';  // 배치키 정보
$currency    = isset($_POST['currency']) ? $_POST['currency'] : '';  // 화폐단위
$service_id  = isset($service_id) ?  $service_id : '';  // 구독 서비스 ID
if (empty($od_id) || empty($service_id) || empty($billing_key) || empty($currency)) {
    response_json('필수 파라미터가 없습니다.', 400);
}

$service_info = get_service_detail($service_id);
if (!is_array($service_info) || empty($service_info)) {
    response_json('구독 정보를 가져오는데 실패했습니다.', 400);
}

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
$customer_ip = get_real_client_ip();

$recurring = $service_info['recurring'];  // 정기 결제의 주기 몇일, 몇개월, 몇년 등.
$recurring_unit = $service_info['recurring_unit']; // 정기결제 주기단위

/**
 * @var bool $bSucc 결제결과 성공여부 변수 (false 일때 결제 취소처리)
 */
$bSucc = false;

//이벤트기간이 설정되면 이벤트가격 선택
$payment_price = $service_info['is_event'] == 1 ? $service_info['event_price'] : $service_info['price'];

$request_billing_data = array(
    'cust_ip' => $customer_ip,
    'amount' => $payment_price,
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
$pg_res_data = $billing->pg->requestBilling($request_billing_data);
$pg_res_data = $billing->convertPgDataToCommonData($pg_res_data);
if (isset($pg_res_data['http_code'])) {
    //pg 사 통신에러 응답.
    response_json($pg_res_data['result_message'], $pg_res_data['http_code']);
}

/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */


/* ============================================================================== */
/* =  pg 사 응답정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
$result_code = $pg_res_data['result_code'];
$result_message = $pg_res_data['result_message'];

if ($result_code === '0000') {
    if ($payment_price == $pg_res_data['amount']) { //결제된 금액과 서비스 금액 같은지 확인.
        $bSucc = true;
    }
}
//부정행위, 결제 실패도 기록으로 남기기 위해 다음 단계로 진행.

/* ==============================================================================
 결제 결과처리
 자동결제 정보 저장

==========================================================================
승인 결과 DB 저장 실패시 : 자동취소
--------------------------------------------------------------------------
승인 결과를 DB에 기록하는 과정에서 정상적으로 승인된 건에 대해
DB 작업이 실패하여 완료되지 않은 경우, 자동으로
승인 취소 요청을 하는 프로세스로 구성되어 있습니다.

DB 작업이 실패 한 경우, 각 테이블의 저장여부 변수의 값을 false 로 설정해 주시기 바랍니다.
bSucc 및 각 테이블에 기록하는 작업이 하나라도 false 인경우 승인이 취소됩니다.
--------------------------------------------------------------------------
*/

$payment_data = array();
$payment_data = array_merge($payment_data, $pg_res_data);

$start_date = date('Y-m-d H:i:s');
if (empty($service_info['expiration'])) {
    $end_date = '0000-00-00 00:00:00';//구독 만료기간이 정해지지않음.
} else {
    $end_date = $billing->nextPaymentDate($start_date, $start_date, $service_info['expiration'], $service_info['expiration_unit']);
}

$next_payment_date = $billing->nextPaymentDate($start_date, $start_date, $recurring, $recurring_unit);

if ($next_payment_date !== false && $bSucc === true) {
    //서비스 구독
    $billing_price          = $pg_res_data['amount'];
    $event_expiration_date  = '0000-00-00 00:00:00';
    $event_price            = 0;

    // 첫 구독 이벤트
    if ($service_info['is_event'] == '1' && !empty($service_info['event_period'])) {
        $billing_price          = $service_info['price'];
        $event_expiration_date  = $billing->nextPaymentDate($start_date, $start_date, $service_info['event_period'], $service_info['event_unit']);
        $event_price            = $service_info['event_price'];
    }
    // 구독만료기간이 없을 경우 결제정보가격 0원처리
    if ($billing->isNullByDate($end_date)) {
        $billing_price = 0;
    }
    
    $payment_data['price'] = $billing_price;
    $payment_data['event_expiration_date'] = $event_expiration_date;
    $payment_data['event_price'] = $event_price;
    $payment_data['mb_id'] = get_user_id();
    $payment_data['service_id'] = $service_id;
    $payment_data['billing_key'] = $billing_key;
    $payment_data['start_date'] = $start_date;
    $payment_data['end_date'] = $end_date;
    $payment_data['next_payment_date'] = $next_payment_date;
    $payment_data['status'] = 1;

    $is_insert_billing_information = $information_model->insert($payment_data);
}

// 자동결제 이력 저장

// 결제일, 구독만료일
$payment_data['payment_date'] = date('Y-m-d');
$expiration_date = $billing->nextPaymentDate(G5_TIME_YMD, G5_TIME_YMD, $service_info['recurring'], $service_info['recurring_unit']);

$payment_data['expiration_date'] = date('Y-m-d 23:59:59 ', strtotime($expiration_date));
$payment_data['payment_count'] = 1; //첫 결제
$is_insert_history = $history_model->insert($payment_data);

//0000 은 KCP 결제성공
if ($result_code === '0000') {
    if ($bSucc && $is_insert_history && (isset($is_insert_billing_information) && $is_insert_billing_information)) {
        $res_data = array();
        $res_data['result_code'] = $result_code;
        $res_data['result_message'] = $result_message;

        response_json($res_data, 200);
    } else {
        //결제 취소 및 취소 이력저장
        $cancel_res = $billing->pg->requestCancelBilling($payment_data['payment_no']);
        $cancel_res = $billing->convertPgDataToCommonData($cancel_res);

        $cancel_data = array();
        $error_http_code = isset($pg_response['http_code']) ? $pg_response['http_code'] : 0;
        $cancel_data['od_id'] = $payment_data['od_id'];
        $cancel_data['payment_no'] = $payment_data['payment_no'];
        $cancel_data['type'] = 'all';
        $cancel_data['result_code'] = isset($cancel_res['result_code']) ? $cancel_res['result_code'] : $error_http_code;
        $cancel_data['result_message'] = isset($cancel_res['result_message']) ? $cancel_res['result_message'] : $billing_conf['bc_pg_code'] . '사 결제 취소 실패';
        $cancel_data['cancel_no'] = $cancel_res['cancel_no'];
        $cancel_data['cancel_reason'] = '결제 실패';
        $cancel_data['cancel_amount'] = $payment_price;
        $cancel_data['refundable_amount'] = $payment_price;

        $cancel_model->insert($cancel_data);
        response_payment_cancel($cancel_res);
    }
} else {
    response_json('결제 승인이 되지 않았습니다.', 200); //결제 실패기록이 남는다.
}

/**
 * 결제 취소 요청 응답
 * @param $cancel_res
 * @return void
 */
function response_payment_cancel($cancel_res)
{
    // 유효성 검사.
    if (!isset($cancel_res['result_code']) || $cancel_res['result_code'] !== '0000') {
        $msg = '결제 취소가 실패했습니다. 관리자 문의바랍니다.';
        response_json($msg, 401);
    }

    response_json('결제 승인이 취소되었습니다.', 200);
}