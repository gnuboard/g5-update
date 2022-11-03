<?php
header("Content-type: text/html; charset=utf-8");

include_once './_common.php';

include_once G5_PATH . "/bbs/kcp-batch/config.php";
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
// 로그 테이블 저장
$g5["kcp_batch_key_log_table"] = "g5_kcp_batch_key_log";
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

// 결과 출력
echo $res_data;
exit;
