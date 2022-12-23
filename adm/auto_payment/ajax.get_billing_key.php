<?php
$sub_menu = '800940';
include_once './_common.php';

$billing            = new Billing($billing_conf['bc_pg_code']);
$information_model  = new BillingInformationModel();
$key_history_model  = new BillingKeyHistoryModel();
$billing_history_model = new BillingHistoryModel();

/* ============================================================================== */
/* =  요청정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
$od_id = clean_xss_tags($_POST['ordr_idxx']);
$card_no = clean_xss_tags($_POST['card_mask_no']);

//구독정보의 카드 업데이트에 회원 id가 필요.
$billing_info = $information_model->selectOneByOrderId($od_id);

if (empty($od_id) || empty($billing_info) || !isset($billing_info['mb_id'])) {
    response_json('필수 파라미터가 없습니다.', 400);
}

$mb_id = $billing_info['mb_id'];

/* ============================================================================== */
/* =  요청                                                                      = */
/* = -------------------------------------------------------------------------- = */
$pg_res_data = $billing->pg->requestIssueBillKey($_POST);
$pg_res_data = $billing->convertPgDataToCommonData($pg_res_data);
/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */

// pg 에러
if (isset($pg_res_data['http_code'])) {
    response_json($pg_res_data['result_message'], $pg_res_data['http_code']);
}

$result_code = isset($pg_res_data['result_code']) ? $pg_res_data['result_code'] : '';
$billing_key = isset($pg_res_data['billing_key']) ? $pg_res_data['billing_key'] : '';

$history_data = array(
    'pg_code' => $billing_conf['bc_pg_code'],
    'od_id' => $od_id,
    'mb_id' => $mb_id,
    'card_no' => $card_no,
    'card_code' => isset($pg_res_data['card_code']) ? $pg_res_data['card_code'] : '',
    'card_name' => isset($pg_res_data['card_name']) ? $pg_res_data['card_name'] : '',
    'result_code' => $result_code,
    'result_message' => isset($pg_res_data['result_message']) ? $pg_res_data['result_message'] : '',
    'billing_key' => $billing_key
);

/* ============================================================================== */
/* =   결과처리 및 출력                                                          = */
/* ============================================================================== */
// 로그 테이블 저장
$key_history_model->insert($history_data);

// 결제정보 배치 키 변경
if ($result_code === "0000") {
    $information_model->updateBillingKey($od_id, $billing_key);
    $pg_res_data['display_billing_key'] = $billing->displayBillKey($billing_key);
}
// 나머지 결과 출력
response_json($pg_res_data);
