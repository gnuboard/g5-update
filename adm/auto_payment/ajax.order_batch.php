<?php
// 결제 요청 처리.
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

/* ============================================================================== */
/* =  결제 요청정보 준비                                                           = */
/* = -------------------------------------------------------------------------- = */
$g5Mysqli = new G5Mysqli();
$kcpBatch = new KcpBatch();

/** 결제정보 / 구독정보 조회 */
$sql = "SELECT * FROM {$g5['batch_payment_table']} WHERE id = ? AND od_id = ?";
$payment_info = $g5Mysqli->getOne($sql, array($_POST['id'], $_POST['ordr_idxx']));
if (!$payment_info) {
    responseJson('이전 결제정보를 찾을 수 없습니다.', 400);
}
$sql = "SELECT 
            bi.*, 
            bs.*,
            mb.mb_name, mb.mb_email, mb.mb_tel
        FROM {$g5['batch_info_table']} bi 
        LEFT JOIN {$g5['batch_service_table']} bs on bi.service_id = bs.service_id
        LEFT JOIN {$g5['member_table']} mb on bi.mb_id = mb.mb_id
        WHERE od_id = ?";
$service_info = $g5Mysqli->getOne($sql, array($_POST['ordr_idxx']));
if (!$service_info) {
    responseJson('구독정보를 찾을 수 없습니다.', 400);
}

/** 필수 파라미터 */
$kcp_cert_info  = $kcpBatch->getServiceCertification();
$site_cd        = $kcpBatch->getSiteCd();
$bt_group_id    = $kcpBatch->getKcpGroupId();   // 배치키 그룹아이디

$bt_batch_key   = $payment_info['batch_key'];   // 배치키 정보
$amount         = $payment_info['amount'];      // 결제금액
$ordr_idxx      = $payment_info['od_id'];       // 주문 번호

if (empty($bt_batch_key) || empty($amount) || empty($ordr_idxx)) {
    responseJson('필수 파라미터가 없습니다.', 400);
}

/**
 * 권장 파라미터
 * @var string $good_name (100byte 이내) 상품명
 * TODO 100바이트 cut
 */
$good_name          = $service_info['service_name'];

/** 선택 파라미터 */
$buyr_name          = !empty($service_info['mb_name']) ? $service_info['mb_name'] : '';
$buyr_mail          = !empty($service_info['mb_email']) ? $service_info['mb_email'] : '';
$buyr_tel2          = !empty($service_info['mb_tel']) ? $service_info['mb_tel'] : '';

/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = true;

/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$data = array(
    'site_cd'       => $site_cd,
    'kcp_cert_info' => $kcp_cert_info,
    'pay_method'    => 'CARD',
    'cust_ip'       => '',
    'amount'        => $amount,
    'card_mny'      => $amount,
    'currency'      => '410',
    'quota'         => '00',
    'ordr_idxx'     => $ordr_idxx,
    'good_name'     => $good_name,
    'buyr_name'     => $buyr_name,
    'buyr_mail'     => $buyr_mail,
    'buyr_tel2'     => $buyr_tel2,
    'card_tx_type'  => '11511000',
    'bt_batch_key'  => $bt_batch_key,
    'bt_group_id'   => $bt_group_id
);

$res_data = $kcpBatch->requestApi($kcpBatch->urlBatchPayment, $data);
if (is_array($res_data)) {
    //error 응답.
    responseJson($res_data['msg'], $res_data['http_code']);
}

/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */


/* ============================================================================== */
/* =  응답정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
// 공통
$res_cd = "";
$res_msg = "";
$tno = "";
$amount = "";
$order_no = "";

// 카드
$card_cd = "";
$card_name = "";
$app_no = "";
$app_time ="";
$quota ="";
$noinf ="";

// Res JSON DATA Parsing
$json_res = json_decode($res_data, true);

$res_cd = $json_res['res_cd'];
$res_msg = $json_res['res_msg'];

if ( $res_cd === '0000') {
    $tno = $json_res['tno'];
    $amount = $json_res['amount'];
    $card_cd = $json_res['card_cd'];
    $card_name = $json_res['card_name'];
    $app_no = $json_res['app_no'];
    $order_no = $json_res['order_no'];
    $app_time = $json_res['app_time'];
    $quota = $json_res['quota'];
    $noinf = $json_res['noinf'];
}

/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
$bind_param = array(
    $ordr_idxx,
    $member['mb_id'],
    $bt_batch_key,
    $payment_info['payment_count'],
    $amount,
    $res_cd,
    $res_msg,
    $tno,
    $card_name,
    $res_data
);
// 자동결제 이력 저장
$sql_payment = "INSERT INTO {$g5['batch_payment_table']} SET 
                od_id           = ?,
                mb_id           = ?,
                batch_key       = ?,
                payment_count   = ?,
                amount          = ?,
                res_cd          = ?,
                res_msg         = ?,
                tno             = ?,
                card_name       = ?,
                res_data        = ?,
                date            = now()";
$result = $g5Mysqli->execSQL($sql_payment, $bind_param, true);
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

DB 작업이 실패 한 경우, bSucc 라는 변수의 값을 "false"로 설정해 주시기 바랍니다.
(DB 작업 성공의 경우에는 "false" 이외의 값을 설정하시면 됩니다.)
--------------------------------------------------------------------------
*/
//0000 은 성공
if ( $res_cd == '0000')
{
    if ( $bSucc === false)
    {
        // API RES
        $res_data  = $kcpBatch->cancelBatchPayment($tno);

        // RES JSON DATA Parsing
        $json_res = json_decode($res_data, true);

        // $json_res["res_cd" ] = "9999";//$json_res["res_cd" ];
        $json_res['res_msg'] = $json_res['res_msg'] . "(DB 입력오류로 인한 결제취소처리)";//$json_res["res_msg"];
    }
}

// 결과 출력
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