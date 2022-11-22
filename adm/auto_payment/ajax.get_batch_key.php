<?php
// 배치키 재발급
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';
require_once G5_PATH . '/bbs/kcp-batch/Billing.php';
require_once G5_PATH . '/bbs/kcp-batch/BillingInterface.php';
require_once G5_PATH . '/bbs/kcp-batch/G5BillingKcp.php';
require_once G5_PATH . '/bbs/kcp-batch/G5BillingToss.php';

$billing = new Billing('kcp');

/* ============================================================================== */
/* =  요청정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
$od_id = clean_xss_tags($_POST['ordr_idxx']);

/* ============================================================================== */
/* =  요청                                                                      = */
/* = -------------------------------------------------------------------------- = */
// $res_data = $billing->requestIssueBillKey($data);
$res_data = $billing->pg->requestIssueBillKey($_POST);
$res_data = $billing->convertPgDataToCommonData($res_data);
/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
$result_code    = $res_data['result_code'];
$bill_key       = $res_data['bill_key'];

/* ============================================================================== */
/* =   결과처리 및 반환                                                          = */
/* ============================================================================== */
// 로그 테이블 저장
$billing->insertIssueBillKeyLog($mb_id, $res_data);

// 결제정보 배치 키 변경
if ($result_code == "0000") {
    $billing->updateBillKey($od_id, $bill_key);
    // 배치키 * 표시
    $res_data['display_bill_key'] = $billing->displayBillKey($bill_key);
}
// 결과 출력
echo json_decode($res_data);
exit;
