<?php

header('Content-type: application/json; charset=utf-8');
require_once dirname(__FILE__) . '/_common.php';
require_once dirname(__FILE__) . '../../subscription/subscription_service.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';
define('WON', '410'); // 원화

$od_id = isset($_POST['od_id']) ? $_POST['od_id'] : '';  // 주문 정보
$currency = isset($_POST['currency']) ? $_POST['currency'] : WON;  // 화폐단위
$service_id = isset($_POST['service_id']) ? $service_id : '';  // 구독 서비스 ID

if (empty($service_id) || empty($od_id)) {
    responseJson('필수 파라미터가 없습니다.', 400);
}

$subscribeService = showServiceDetail($service_id);
$amount = $subscribeService[0]['price'];
$good_name = $subscribeService[0]['name'];
$return_url = G5_BBS_URL . '/kcp-batch/ajax.mobile_redirect.php';

$kcpBatch = new KcpBatch();
$result = $kcpBatch->tradeRegister($od_id, $amount, $good_name, $return_url, 'N');
$res_data = json_decode($result, true);
/*
============================
거래등록 응답정보
----------------------------
*/

if (is_array($result)) {
    //error 발생.
    responseJson($res_data['Message'], $res_data['http_code']);
}

if ($res_data['Code'] !== '0000') {
    responseJson($res_data['Message'], 400);
}

$res_cd = $res_data['Code']; // 응답코드
$res_msg = $res_data['Message']; // 응답메세지
$approvalKey = $res_data['approvalKey']; // 거래등록키
$traceNo = $res_data['traceNo']; // 추적번호
$payUrl = $res_data['PayUrl']; // 거래등록 PAY URL

$res = array(
    'msg'          => $res_msg,
    'approval_key' => $approvalKey,
    'res_cd'       => $res_cd,
    'tno'          => $traceNo,
    'pay_url'      => stripslashes($payUrl)
);

if (PHP_VERSION_ID >= 50400) {
    //역슬래시 제거
    echo str_replace('\\/', '/', json_encode($res, JSON_UNESCAPED_UNICODE));
    exit;
}


echo str_replace('\\/', '/', to_han(json_encode($res)));
exit;
