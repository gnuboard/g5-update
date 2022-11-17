<?php
// 결제 요청 처리.
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';
require_once G5_PATH . '/bbs/kcp-batch/Billing.php';
require_once G5_PATH . '/bbs/kcp-batch/BillingInterface.php';
require_once G5_PATH . '/bbs/kcp-batch/G5BillingKcp.php';
require_once G5_PATH . '/bbs/kcp-batch/G5BillingToss.php';

$billing = new Billing('kcp');
$g5Mysqli = new G5Mysqli();

/* ============================================================================== */
/* =  결제 요청정보 준비                                                         = */
/* = -------------------------------------------------------------------------- = */

/** 결제정보 / 구독정보 조회 */
$sql = "SELECT od_id, amount, batch_key, payment_count FROM {$g5['batch_payment_table']} WHERE id = ? AND od_id = ?";
$payment_info = $g5Mysqli->getOne($sql, array($_POST['id'], $_POST['ordr_idxx']));
if (!$payment_info) {
    responseJson('이전 결제정보를 찾을 수 없습니다.', 400);
}
$sql = "SELECT 
            bs.service_name,
            mb.mb_name, mb.mb_email, mb.mb_tel
        FROM {$g5['batch_info_table']} bi 
        LEFT JOIN {$g5['batch_service_table']} bs on bi.service_id = bs.service_id
        LEFT JOIN {$g5['member_table']} mb on bi.mb_id = mb.mb_id
        WHERE od_id = ?";
$service_info = $g5Mysqli->getOne($sql, array($_POST['ordr_idxx']));
if (!$service_info) {
    responseJson('구독정보를 찾을 수 없습니다.', 400);
}

/** 필수 파라미터 체크 */
if (empty($payment_info['batch_key']) || empty($payment_info['amount']) || empty($payment_info['od_id'])) {
    responseJson('필수 파라미터가 없습니다.', 400);
}
/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = true;

/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$data = array_merge($payment_info, $service_info);
$json_res = $billing->pg->requestBilling($data);
$json_res = $billing->convertPgDataToCommonData($json_res);

// Res JSON DATA Parsing
if (isset($json_res['http_code'])) {
    //error 응답.
    responseJson($json_res['result_msg'], $json_res['http_code']);
}
/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */

/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
$result = $billing->insertBillingLog($member['mb_id'], $payment_info, $json_res);
if ($result <= 0) {
    $bSucc = false;
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
if ( $json_res['result_code'] === '0000' && $bSucc === false)
{
    $cancle_res = $billing->pg->requestCancelBilling($json_res['billing_no']);
    if($cancle_res['kcp_sign_data'] === false){
        responseJson('결제 취소가 실패했습니다. 관리자에게 문의바랍니다.', 401);
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
