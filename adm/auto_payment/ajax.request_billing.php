<?php
$sub_menu = '800940';
include_once './_common.php';

$billing            = new Billing($billing_conf['bc_pg_code']);
$information_model  = new BillingInformationModel();
$history_model      = new BillingHistoryModel();
$cancel_model       = new BillingCancelModel();

/**
 * @var bool $bSucc 결제결과 후처리 성공여부 변수 (false일때 결제 취소처리)
 */
$bSucc = true;
/* ============================================================================== */
/* =  결제 요청정보 준비                                                         = */
/* = -------------------------------------------------------------------------- = */
$od_id      = clean_xss_tags($_POST['od_id']);
$mb_id      = clean_xss_tags($_POST['mb_id']);
$history_id = clean_xss_tags($_POST['id']);

/** 결제정보 조회 */
$billing_info = $information_model->selectOneByOrderId($od_id);
if (!$billing_info || $mb_id != $billing_info['mb_id']) {
    response_json('구독정보를 찾을 수 없습니다.', 400);
}
/** 결제이력 조회 */ 
$history_info = $history_model->selectOneById($history_id);
if (!$history_info || $mb_id != $history_info['mb_id']) {
    response_json('이전 결제정보를 찾을 수 없습니다.', 400);
}
/** 필수 파라미터 체크 */
if (empty($history_info['billing_key']) || empty($history_info['amount']) || empty($history_info['od_id'])) {
    response_json('필수 파라미터가 없습니다.', 400);
}
/* ============================================================================== */
/* =  결제 요청                                                                  = */
/* = -------------------------------------------------------------------------- = */
$data = array_merge($billing_info, $history_info);
$res_data = $billing->pg->requestBilling($data);
$res_data = $billing->convertPgDataToCommonData($res_data);
/* ============================================================================== */
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
// Res JSON DATA Parsing
if (isset($res_data['http_code'])) {
    response_json($res_data['result_message'], $res_data['http_code']);
}

$res_data['mb_id']              = $history_info['mb_id'];
$res_data['billing_key']        = $history_info['billing_key'];
$res_data['payment_count']      = $history_info['payment_count'];
$res_data['payment_date']       = date('Y-m-d H:i:s'); // @todo 응답받은 시간으로 입력필요
$res_data['expiration_date']    = $billing->nextPaymentDate($billing_info['start_date'], $res_data['payment_date'], $billing_info['recurring'], $billing_info['recurring_unit']);
// $res_data['expiration_date']    = date('Y-m-d 23:59:59 ', strtotime('+' . $billing_info['recurring'] . " " . $unit_array[$billing_info['recurring_unit']]));
/* ============================================================================== */
/* =  로그파일 생성                                                              = */
/* = -------------------------------------------------------------------------- = */
// 작업예정

/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
$result = $history_model->insert($res_data);
if ($result_code == "0000") {
    if (!$result) {
        $bSucc = false;
    } else {
        $result = $information_model->updateNextPaymentDate($od_id, date('Y-m-d', strtotime($res_data['expiration_date'])));
        if (!$result) {
            $bSucc = false;
        }
    }
}
/*
==========================================================================
승인 결과 DB 처리 실패시 : 자동취소
--------------------------------------------------------------------------
승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해
DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로
승인 취소 요청을 하는 프로세스가 구성되어 있습니다.

DB 작업이 실패 한 경우, bSucc 라는 변수의 값을 false로 설정해 주시기 바랍니다.
(DB 작업 성공의 경우에는 bSucc 는 true 입니다. );
--------------------------------------------------------------------------
*/
//0000 은 성공
if ($res_data['result_code'] === '0000' && $bSucc === false) {
    $reason = '가맹점 DB 처리 실패(자동취소)';
    $cancel_res = $billing->pg->requestCancelBilling($res_data['payment_no'], $reason);
    $cancel_res = $billing->convertPgDataToCommonData($cancel_res);
    
    // 취소이력 저장
    $cancel_res['od_id']            = $od_id;
    $cancel_res['cancel_reason']    = $reason;
    $cancel_res['cancel_amount']    = $res_data['amount'];
    $cancel_model->insert($cancel_res);
    
    if ($cancel_res['result_code'] !== '0000') {
        response_json('결제 취소가 실패했습니다.' . $cancel_res['result_message'], 401);
    }
}

// 나머지 결과 출력
response_json($res_data);