<?php

require_once dirname(__FILE__) . '/_common.php';
require_once G5_BBS_PATH . '/subscription/subscription_service.php';

$billing_history = new BillingKeyHistoryModel();
/* ===================================================== */
/* =  요청정보                                          = */
/* = ------------------------------------------------- = */
// 필수 파라미터
$tran_cd = isset($_POST['tran_cd']) ? $_POST['tran_cd'] : '';   // 요청코드
$enc_data = isset($_POST['enc_data']) ? $_POST['enc_data'] : ''; // 암호화 데이터
$enc_info = isset($_POST['enc_info']) ? $_POST['enc_info'] : ''; // 결제창 인증결과 암호화 정보
$od_id = isset($_POST['od_id']) ? $_POST['od_id'] : '';
$card_no = isset($_POST['card_mask_no']) ? $_POST['card_mask_no'] : ''; //batch_cardno_return_yn 설정시
$member_id = get_user_id();
/**
 * @var Billing $billing subscription_service.php 선언
 */
$site_cd = $billing->pg->getSiteCd();

if (empty($tran_cd) || empty($enc_data) || empty($enc_info) || empty($od_id)) {
    response_json('필수 파라미터가 없습니다.', 400);
}

if ($site_cd !== trim($_POST['site_cd'])) {
    response_json('파라미터가 유효하지 않습니다.', 400);
}

/* ====================================================  */
/* =  배치키 발급 요청                                   = */
/* = ------------------------------------------------- = */
$billing_key_res_data = $billing->pg->requestIssueBillKey(array(
    "enc_data" => $enc_data,
    "enc_info" => $enc_info,
    "tran_cd" => $tran_cd
));

/* ====================================================  */
/* =  pg 응답정보                                          = */
/* = ------------------------------------------------- = */
$pg_res_data = $billing->convertPgDataToCommonData($billing_key_res_data);

//pg 에러
if (isset($pg_res_data['http_code'])) {
    response_json($pg_res_data['result_message'], $pg_res_data['http_code']);
}
/* ====================================================  */
/* =   결과처리 및 출력                                 = */
/* = ------------------------------------------------- = */
// 로그 테이블 저장

$history_data = array(
    'pg_code' => $billing_conf['bc_pg_code'],
    'od_id' => $od_id,
    'mb_id' => $member_id,
    'card_no' => $card_no,
    'card_code' => isset($pg_res_data['card_code']) ? $pg_res_data['card_code'] : '',
    'card_name' => isset($pg_res_data['card_name']) ? $pg_res_data['card_name'] : '',
    'result_code' => isset($pg_res_data['result_code']) ? $pg_res_data['result_code'] : '',
    'result_message' => isset($pg_res_data['result_message']) ? $pg_res_data['result_message'] : '',
    'billing_key' => isset($pg_res_data['billing_key']) ? $pg_res_data['billing_key'] : ''
);

$billing_history->insert($history_data);

// 결과 출력
if ($pg_res_data['result_code'] === '0000') {
    $result = array(
        'result_code' => $pg_res_data['result_code'],
        'billing_key' => $pg_res_data['billing_key'],
        'enc_info' => $enc_info,
        'tran_cd' => $tran_cd
    );
    response_json($result);
}

// 나머지 결과 출력
if (isset($pg_res_data['result_code'])) {
    response_json($pg_res_data);
} else {
    response_json($pg_res_data['result_message']);
}
