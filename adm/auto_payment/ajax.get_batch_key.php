<?php
header("Content-type: text/html; charset=utf-8");

include_once './_common.php';

include_once G5_PATH . "/bbs/kcp-batch/KcpBatch.php";

/* ============================================================================== */
/* =  요청정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
$tran_cd            = $_POST["tran_cd"]; // 요청코드
$enc_data           = $_POST["enc_data"]; // 암호화 인증데이터
$enc_info           = $_POST["enc_info"]; // 암호화 인증데이터
// 인증서 정보(직렬화)
$kcpBatch           = new KcpBatch();
$kcp_cert_info      = $kcpBatch->getServiceCertification();

$data = array(
    "tran_cd"        => $tran_cd,
    "site_cd"        => $site_cd,
    "kcp_cert_info"  => $kcp_cert_info,
    "enc_data"       => $enc_data,
    "enc_info"       => $enc_info
);

/* ============================================================================== */
/* =  요청                                                                      = */
/* = -------------------------------------------------------------------------- = */
$res_data = $kcpBatch->requestApi($kcpBatch->urlGetBatchKey, $data);

/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */

// RES JSON DATA Parsing
$json_res   = json_decode($res_data, true);

$res_cd     = $json_res["res_cd"];
$res_msg    = $json_res["res_msg"];
$card_cd    = "";
$card_name  = "";
$batch_key  = "";

if ($res_cd == "0000") {
    $batch_key  = $json_res["batch_key"];
    $card_cd    = $json_res["card_cd"];
    $card_name  = $json_res["card_name"];
}

/* ============================================================================== */
/* =   결과처리 및 반환                                                          = */
/* ============================================================================== */
$od_id = clean_xss_tags($_POST['ordr_idxx']);

$g5['batch_info_table']             = G5_TABLE_PREFIX . 'batch_info';
$g5['batch_service_table']          = G5_TABLE_PREFIX . 'batch_service';
$g5['batch_service_price_table']    = G5_TABLE_PREFIX . 'batch_service_price';
$g5['batch_service_date_table']     = G5_TABLE_PREFIX . 'batch_service_date';
$g5["kcp_batch_key_log_table"]      = G5_TABLE_PREFIX . "kcp_batch_key_log";

// 로그 테이블 저장
$sql = "INSERT INTO {$g5["kcp_batch_key_log_table"]} SET 
                mb_id               = '{$member["mb_id"]}',
                res_cd              = '{$res_cd}',
                res_msg             = '{$res_msg}',
                card_cd             = '{$card_cd}',
                card_name           = '{$card_name}',
                batch_key           = '{$batch_key}',
                date                = '" . date("Y-m-d H:i:s") . "'
            ";
sql_query($sql);
// 결제정보 배치 키 변경
if ($res_cd == "0000") {
    $sql = "UPDATE {$g5['batch_info_table']} SET batch_key = '{$batch_key}' WHERE od_id = '{$od_id}'";
    sql_query($sql);

    // 배치키 * 표시
    $offset = 4;
    $repeat_time = 8;
    $json_res['display_batch_key'] = substr_replace($json_res["batch_key"], str_repeat('*', $repeat_time), $offset, $repeat_time);
}

// 결과 출력
echo json_decode($json_res);
exit;
