<?php
header('Content-type: application/json; charset=utf-8');

include_once './_common.php';
include_once G5_LIB_PATH . '/billing/KcpBatch.php';

/* ===================================================== */
/* =  요청정보                                          = */
/* = ------------------------------------------------- = */
// 필수 파라미터
$tran_cd  = isset($_POST['tran_cd']) ? $tran_cd : '';   // 요청코드
$enc_data = isset($_POST['enc_data']) ? $enc_data : ''; // 암호화 데이터
$enc_info = isset($_POST['enc_info']) ? $enc_info : ''; // 결제창 인증결과 암호화 정보
$od_id = isset($_POST['ordr_idxx']) ? $ordr_idxx : '';

// 인증서 정보(직렬화)
$kcpBatch      = new KcpBatch();
$site_cd       = $kcpBatch->getSiteCd();
$kcp_cert_info = $kcpBatch->getServiceCertification();

if (empty($tran_cd) || empty($enc_data) || empty($enc_info) || empty($kcp_cert_info)) {
    responseJson('필수 파라미터가 없습니다.', 400);
}

if ($site_cd !== trim($_POST['site_cd'])) {
    responseJson('파라미터가 유효하지 않습니다.', 400);
}

$data = array(
    "site_cd"        => $site_cd,
    "kcp_cert_info"  => $kcp_cert_info,
    "enc_data"       => $enc_data,
    "enc_info"       => $enc_info,
    "tran_cd"        => $tran_cd
);

/* ====================================================  */
/* =  배치키 발급 요청                                   = */
/* = ------------------------------------------------- = */
$res_data = $kcpBatch->requestApi($kcpBatch->urlGetBatchKey, $data);
if(is_array($res_data)){
    responseJson($res_data['msg'], $res_data['http_code']);
}
/* ====================================================  */
/* =  응답정보                                          = */
/* = ------------------------------------------------- = */

// RES JSON DATA Parsing
$json_res   = json_decode($res_data, true);

$res_cd     = $json_res['res_cd'];
$res_msg    = $json_res['res_msg'];
$card_cd    = '';
$card_name  = '';
$batch_key  = '';
$card_mask_no = ''; //batch_cardno_return_yn 설정시

if ($res_cd == '0000') {
    $batch_key  = $json_res['batch_key'];
    $card_cd    = $json_res['card_cd'];
    $card_name  = $json_res['card_name'];
    $card_mask_no = isset($json_res['card_mask_no']) ? $json_res['card_mask_no'] : '';
}

/* ====================================================  */
/* =   결과처리 및 반환                                 = */
/* = ------------------------------------------------- = */
// 로그 테이블 저장
$g5['billing_key_history_table'] = G5_TABLE_PREFIX . 'billing_key_history';
$sql = "INSERT INTO {$g5['billing_key_history_table']} SET 
    od_id               = '$od_id',
    mb_id               = '{$member['mb_id']}',
    res_cd              = '{$res_cd}',
    res_msg             = '{$res_msg}',
    card_cd             = '{$card_cd}',
    card_name           = '{$card_name}',
    batch_key           = '{$batch_key}',
    card_mask_no        = '$card_mask_no',
    date                = '" . date('Y-m-d H:i:s') . "'
            ";
sql_query($sql);

// 결과 출력
if($res_cd === '0000') {
   echo json_encode(
    array('msg' => $res_msg,
        'res_cd' => $res_cd,
        'batch_key' => $batch_key
        )
    );
} else {
    if(is_array($json_res)){
        responseJson($res_msg, 400);
    } else {
        responseJson($json_res, 400);
    }
}