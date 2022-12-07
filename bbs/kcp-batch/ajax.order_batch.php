<?php
header("Content-type: text/html; charset=utf-8");

require_once dirname(__FILE__) . '/_common.php';
include_once G5_PATH . "/bbs/kcp-batch/KcpBatch.php";

/* ============================================================================== */
/* =  요청정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
// 인증서 정보(직렬화)
$kcpBatch           = new KcpBatch();
$kcp_cert_info      = $kcpBatch->getServiceCertification();
$site_cd            = $kcpBatch->getSiteCd();
$cust_ip            = '';
$currency           = $_POST[ "currency" ];
$quota              = '';

$ordr_idxx          = $_POST[ "ordr_idxx" ];
$good_name          = $_POST[ "good_name" ];
$buyr_name          = $_POST[ "buyr_name" ];
$buyr_mail          = $_POST[ "buyr_mail" ];
$buyr_tel2          = $_POST[ "buyr_tel2" ];

$bt_batch_key       = $_POST[ "bt_batch_key" ]; // 배치키 정보
$bt_group_id        = $kcpgroup_id;             // 배치키 그룹아이디

$recurring    = '1';//$_POST["recurring"];
$interval_unit      = 'm';//$_POST["interval_unit"];
// 결제결과 후처리 성공여부 (false일때 결제 취소처리)
$bSucc = "";

$data = array(
    "site_cd"        => $site_cd,
    "kcp_cert_info"  => $kcp_cert_info,
    "pay_method"     => "CARD",
    "cust_ip"        => "",
    "amount"         => $_POST[ "good_mny" ],
    "card_mny"       => $_POST[ "good_mny" ],
    "currency"       => $currency,
    "quota"          => "00",
    "ordr_idxx"      => $ordr_idxx,
    "good_name"      => $good_name,
    "buyr_name"      => $buyr_name,
    "buyr_mail"      => $buyr_mail,
    "buyr_tel2"      => $buyr_tel2,
    "card_tx_type"   => "11511000",
    "bt_batch_key"   => $bt_batch_key,
    "bt_group_id"    => $bt_group_id
);

/* ============================================================================== */
/* =  요청                                                                      = */
/* = -------------------------------------------------------------------------- = */
$res_data = $kcpBatch->requestApi($kcpBatch->urlBatchPayment, $data);


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

// RES JSON DATA Parsing
$json_res = json_decode($res_data, true);

$res_cd = $json_res["res_cd"];
$res_msg = $json_res["res_msg"];

if ( $res_cd == "0000" )
{
    $tno = $json_res["tno"];
    $amount = $json_res["amount"];
    $card_cd = $json_res["card_cd"];
    $card_name = $json_res["card_name"];
    $app_no = $json_res["app_no"];
    $order_no = $json_res["order_no"];
    $app_time = $json_res["app_time"];
    $quota = $json_res["quota"];
    $noinf = $json_res["noinf"];
}

/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
// 자동결제 정보 저장
$start_date = date("Y-m-d H:i:s");
$end_date = "0000-00-00 00:00:00";

$g5["batch_info_table"] = "g5_batch_info";
$sql_batch_info = "INSERT INTO {$g5["batch_info_table"]} SET 
                od_id               = '{$ordr_idxx}',
                mb_id               = '{$member['mb_id']}',
                batch_key           = '{$bt_batch_key}',
                kcpgroup_id         = '{$bt_group_id}',
                price               = '{$amount}',
                recurring           = '{$recurring}',
                interval_unit       = '{$interval_unit}',
                start_date          = '{$start_date}',
                end_date            = '{$end_date}'
            ";
if (!sql_query($sql_batch_info)) {
    $bSucc = "false";
}

// 자동결제 이력 저장
$g5["batch_payment_table"] = "g5_batch_payment";
$sql_payment = "INSERT INTO {$g5["batch_payment_table"]} SET 
                od_id               = '{$ordr_idxx}',
                mb_id               = '{$member["mb_id"]}',
                batch_key           = '{$bt_batch_key}',
                amount              = '{$amount}',
                res_cd              = '{$res_cd}',
                res_msg             = '{$res_msg}',
                tno                 = '{$tno}',
                card_name           = '{$card_name}',
                res_data            = '{$res_data}',
                date                = '{$start_date}'
            ";
if (!sql_query($sql_payment)) {
    $bSucc = "false";
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

if ( $res_cd == "0000" )
{
    if ( $bSucc == "false")
    {        
        // API RES
        $res_data  = $kcpBatch->cancelBatchPayment($tno);
        
        // RES JSON DATA Parsing
        $json_res = json_decode($res_data, true);
        
        // $json_res["res_cd" ] = "9999";//$json_res["res_cd" ];
        $json_res["res_msg"] = $json_res["res_msg"] . "(DB 입력오류로 인한 결제취소처리)";//$json_res["res_msg"];
    }
}

// 결과 출력
if (version_compare(phpversion(), "5.4", ">=")) {
    echo json_encode($json_res, JSON_UNESCAPED_UNICODE);
} else {
    function han ($s) { return reset(json_decode('{"s":"'.$s.'"}')); }
    function to_han ($str) { return preg_replace('/(\\\u[a-f0-9]+)+/e', 'han("$0")', $str); }

    echo to_han(json_encode($json_res));
}
exit;
