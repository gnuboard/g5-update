<?php
/** @todo 관리가능 & global로 변경 */
$pg_code = 'kcp';
include_once './_common.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

$billing = new Billing($pg_code);
$information_model  = new BillingInformationModel();
$history_model      = new BillingHistoryModel();

$unit_array = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day');
/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = true;
/* ============================================================================== */
/* =  결제 요청정보 준비                                                         = */
/* = -------------------------------------------------------------------------- = */
$od_id      = clean_xss_tags($_POST['od_id']);
$mb_id      = clean_xss_tags($_POST['mb_id']);
$history_id = clean_xss_tags($_POST['id']);

/** 결제정보 조회 */
$billing_info = $information_model->selectOneByOrderId($od_id);
if (!$billing_info || $mb_id != $billing_info['mb_id']) {
    responseJson('구독정보를 찾을 수 없습니다.', 400);
}
/** 결제이력 조회 */ 
$history_info = $history_model->selectOneById($history_id);
if (!$history_info || $mb_id != $history_info['mb_id']) {
    responseJson('이전 결제정보를 찾을 수 없습니다.', 400);
}
/** 필수 파라미터 체크 */
if (empty($history_info['billing_key']) || empty($history_info['amount']) || empty($history_info['od_id'])) {
    responseJson('필수 파라미터가 없습니다.', 400);
}
/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$data = array_merge($billing_info, $history_info);
$json_res = $billing->pg->requestBilling($data);
$json_res = $billing->convertPgDataToCommonData($json_res);
/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
// Res JSON DATA Parsing
if (isset($json_res['http_code'])) {
    responseJson($json_res['result_message'], $json_res['http_code']);
}
if ($json_res['result_code'] != "0000") {
    responseJson($json_res['result_message'], 400);
}
// 결제일, 구독만료일
$json_res['payment_date']       = date('Y-m-d');
$json_res['expiration_date']    = date('Y-m-d 23:59:59 ', strtotime('+' . $billing_info['recurring'] . " " . $unit_array[$billing_info['recurring_unit']]));
// 결제정보
$json_res = array_merge($json_res, $history_info);
/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */
// 작업예정

/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
$result = $history_model->insert($json_res);
if (!$result) {
    $bSucc = false;
} else {
    $result = $information_model->updateNextPaymentDate($od_id, date('Y-m-d', strtotime($json_res['expiration_date'])));
    if (!$result) {
        $bSucc = false;
    }
}
/*
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
//0000 은 성공
if ($json_res['result_code'] === '0000' && $bSucc === false) {
    $cancle_res = $billing->pg->requestCancelBilling($json_res['payment_no']);
    $cancle_res = $billing->convertPgDataToCommonData($cancle_res);
    /**
     * @todo 취소요청 결과 저장
     */
    if ($cancle_res['result_code'] !== '0000') {
        responseJson('결제 취소가 실패했습니다.' . $cancle_res['result_message'], 401);
    }
}

// 나머지 결과 출력
if (PHP_VERSION_ID >= 50400) {
    echo json_encode($json_res, JSON_UNESCAPED_UNICODE);
} else {
    echo to_han(json_encode($json_res));
}

function han($s)
{
    $result = json_decode('{"s":"' . $s . '"}');
    return reset($result);
}

/**
 * PHP 5.3 이하에서 json_encode JSON_UNESCAPED_UNICODE 구현
 * @param $str
 * @return array|string|string[]|null
 */
function to_han($str)
{
    return preg_replace('/(\\\u[a-f0-9]+)+/e', 'han("$0")', $str);
}

/**
 * json 형식으로 메시지를 출력 후 exit 합니다.
 * @param string $msg
 * @param string $httpStateNo
 * @return void
 */
function responseJson($msg, $httpStateNo = 200)
{
    $resData = array('msg' => $msg);
    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($resData, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($resData));
    }

    header('Content-type: application/json; charset=utf-8', true, $httpStateNo);
    exit;
}
