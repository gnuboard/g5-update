<?php
$sub_menu = '800940';
include_once './_common.php';

$billing            = new Billing($billing_conf['bc_pg_code']);
$information_model  = new BillingInformationModel();
$key_history_model  = new BillingKeyHistoryModel();

/* ============================================================================== */
/* =  요청정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
$od_id = clean_xss_tags($_POST['ordr_idxx']);
$mb_id = clean_xss_tags($_POST['mb_id']);
/* ============================================================================== */
/* =  요청                                                                      = */
/* = -------------------------------------------------------------------------- = */
$res_data = $billing->pg->requestIssueBillKey($_POST);
$res_data = $billing->convertPgDataToCommonData($res_data);
/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
// Res JSON DATA Parsing
if (isset($res_data['http_code'])) {
    responseJson($res_data['result_message'], $res_data['http_code']);
}

$res_data['pg_code'] = $billing_conf['bc_pg_code'];
$res_data['od_id'] = $od_id;
$res_data['mb_id'] = $mb_id;

$result_code    = $res_data['result_code'];
$billing_key    = $res_data['billing_key'];

/* ============================================================================== */
/* =   결과처리 및 반환                                                          = */
/* ============================================================================== */
// 로그 테이블 저장
$key_history_model->insert($res_data);

// 결제정보 배치 키 변경
if ($result_code == "0000") {
    $information_model->updateBillingKey($od_id, $billing_key);
    $res_data['display_billing_key'] = $billing->displayBillKey($billing_key);
}
// 나머지 결과 출력
if (PHP_VERSION_ID >= 50400) {
    echo json_encode($res_data, JSON_UNESCAPED_UNICODE);
} else {
    echo to_han(json_encode($res_data));
}
