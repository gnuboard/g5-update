<?php
//결제 요청 처리.
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

/* ============================================================================== */
/* =  결제 요청정보 준비                                                           = */
/* = -------------------------------------------------------------------------- = */

define('WON', '410'); // 원화

//필수 파라미터
$kcpBatch           = new KcpBatch();
$kcp_cert_info      = $kcpBatch->getServiceCertification();
$site_cd            = $kcpBatch->getSiteCd();

$bt_batch_key      = isset($_POST['bt_batch_key']) ? $bt_batch_key : '';  // 배치키 정보
$bt_group_id       = $kcpBatch->getKcpGroupId();  // 배치키 그룹아이디
$currency          = isset($_POST['currency']) ? $_POST['currency'] : WON;  // 화폐단위
$amount            = isset($_POST['amount']) ? $amount : null;        // 결제금액 0원을 피하기 위해 null
$ordr_idxx         = isset($_POST['ordr_idxx']) ? $ordr_idxx : '';  // 주문 정보

if(empty($bt_batch_key) || empty($recurring_count) || !isset($_POST['site_cd']) || empty($amount) || empty($ordr_idxx)){
    responseJson('필수 파라미터가 없습니다.', 400);
}

if($site_cd !== trim($_POST['site_cd'])){
    responseJson('파라미터가 유효하지 않습니다.', 400);
}

/**
 * 권장 파라미터
 * @var string $good_name (100byte 이내 약 33글자) 상품명
 *
 */
$good_name          = isset($_POST['good_name']) ? utf8_strcut($good_name, 33, '') : '';

//선택 파라미터
$buyr_name          = isset($_POST['buyr_name']) ? $buyr_name : '';
$buyr_mail          = isset($_POST['buyr_mail']) ? $buyr_mail : '';
$buyr_tel2          = isset($_POST['buyr_tel2']) ? $buyr_tel2 : '';

$recurring_count    = isset($_POST['recurring_count']) ? $recurring_count : '';  // 정기결제 횟수
$recurring_unit     = isset($_POST['recurring_unit']) ? $recurring_unit : '';  // 정기결제 주기단위

/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = false;

$data = array(
    'site_cd' => $site_cd,
    'kcp_cert_info' => $kcp_cert_info,
    'pay_method' => 'CARD',
    'cust_ip' => '',
    'amount' => $good_mny,
    'card_mny' => $good_mny,
    'currency' => $currency,
    'quota' => '00',
    'ordr_idxx' => $ordr_idxx,
    'good_name' => $good_name,
    'buyr_name' => $buyr_name,
    'buyr_mail' => $buyr_mail,
    'buyr_tel2' => $buyr_tel2,
    'card_tx_type' => '11511000',
    'bt_batch_key' => $bt_batch_key,
    'bt_group_id' => $bt_group_id
);

/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$res_data = $kcpBatch->requestApi($kcpBatch->urlBatchPayment, $data);
if(is_array($res_data)){
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

if ( $res_cd === '0000')
{
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
// 자동결제 정보 저장
$start_date = date('Y-m-d H:i:s');
$end_date = '0000-00-00 00:00:00'; //구독 만료기간이 정해지지않음.

$g5['batch_info_table'] = 'g5_batch_info';
$sql_batch_info = "INSERT INTO {$g5['batch_info_table']} SET 
                od_id               = '{$od_id}',
                mb_id               = '{$member['mb_id']}',
                batch_key           = '{$bt_batch_key}',
                kcpgroup_id         = '{$bt_group_id}',
                price               = '{$amount}',
                recurring_count     = '{$recurring_count}',
                recurring_unit       = '{$recurring_unit}',
                start_date          = '{$start_date}',
                end_date            = '{$end_date}'
            ";

$result = sql_query($sql_batch_info);
if($result && affectedRowCounter() === 1) {
    $bSucc = true;
}


// 자동결제 이력 저장
$g5['batch_payment_table'] = 'g5_batch_payment';
$sql_payment = "INSERT INTO {$g5['batch_payment_table']} SET 
                od_id               = '{$od_id}',
                mb_id               = '{$member['mb_id']}',
                batch_key           = '{$bt_batch_key}',
                amount              = '{$amount}',
                res_cd              = '{$res_cd}',
                res_msg             = '{$res_msg}',
                tno                 = '{$tno}',
                card_name           = '{$card_name}',
                res_data            = '{$res_data}',
                date                = '{$start_date}'
            ";

$result = sql_query($sql_payment);
if($result && affectedRowCounter() === 1) {
    $bSucc = true;
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

        // 유효성 검사.
        if($res_data['kcp_sign_data'] === false){
            $json_res['res_msg'] = $json_res['res_msg'] . '취소가 실패했습니다. 관리자 문의바랍니다.';
        }

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

function affectedRowCounter()
{
    if (PHP_VERSION_ID >= 50400 && G5_MYSQLI_USE) {
        $affected_row = mysqli_affected_rows($GLOBALS['g5']['link']);
    } else {
        $affected_row = mysql_affected_rows($GLOBALS['g5']['link']);
    }
    return $affected_row;
}