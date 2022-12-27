<?php

/**
 * KCP 모바일 결제시 거래등록 api
 *
 */

require_once dirname(__FILE__) . '/_common.php';
require_once G5_BBS_PATH . '/subscription/subscription_service.php';

$input = json_decode(file_get_contents('php://input'), true);
$od_id = isset($input['od_id']) ? $input['od_id'] : '';  // 주문 정보
$currency = isset($input['currency']) ? $input['currency'] : '';  // 화폐단위
$service_id = isset($input['service_id']) ? $input['service_id'] : '';  // 구독 서비스 ID

if (empty($service_id) || empty($od_id) || empty($currency)) {
    response_json('필수 파라미터가 없습니다.', 400);
}

$service_info = get_service_detail($service_id);
if (!is_array($service_info) || empty($service_info)) {
    response_json('구독 정보를 가져오는데 실패했습니다.', 400);
}

//이벤트기간이 설정되면 이벤트가격 선택
$payment_price = $service_info['is_event'] == 1 ? $service_info['event_price'] : $service_info['price'];
$good_name = $service_info['name'];
$return_url = G5_BBS_URL . '/kcp-batch/ajax.trade_register.php';
$pg_res_data = $billing->pg->requestTradeRegister($od_id, $payment_price, $good_name, $return_url, 'N');
$res_data = $billing->convertPgDataToCommonData($pg_res_data);
$res_data['result_message'] =  isset($res_data['Message']) ? $res_data['Message'] : '';
$res_data['result_code'] =  isset($res_data['Code']) ? $res_data['Code'] : '';
/*
============================
거래등록 응답정보
----------------------------
*/

if (isset($res_data['http_code'])) {
    //error 발생.
    response_json($res_data['result_message'], $res_data['http_code']);
}

if ($res_data['result_code'] !== '0000') {
    response_json($res_data['result_message'], 400);
}

$res_cd  = $res_data['result_code']; // 응답코드
$res_msg = $res_data['result_message']; // 응답메세지
$approvalKey = $res_data['approvalKey']; // 거래등록키
$traceNo = $res_data['traceNo']; // 추적번호
$payUrl  = $res_data['PayUrl']; // 거래등록 PAY URL

$res = array(
    'result_message' => $res_msg,
    'approval_key'  => $approvalKey,
    'result_code'   => $res_cd,
    'traceNo'   => $traceNo,
    'pay_url'   => stripslashes($payUrl),
    'od_id'     => $od_id,
    'service_id'    => $service_id,
    'amount' => $payment_price
);

if (PHP_VERSION_ID >= 50400) {
    //역슬래시 제거
    echo str_replace('\\/', '/', json_encode($res, JSON_UNESCAPED_UNICODE));
} else {
    echo str_replace('\\/', '/', json_encode($res));
}
exit;
