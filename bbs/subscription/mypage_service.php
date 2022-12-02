<?php

/**
 * 구독 마이페이지에 필요함 함수모음
 * json이나 html 은 반환하지 않습니다.
 * 비동기 요청은 ajax.myapge.php 참고.
 */

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once(dirname(__FILE__) . '../../../common.php');
require_once(G5_LIB_PATH . '/billing/G5AutoLoader.php');

$pg_code = 'kcp';
$billing = new Billing($pg_code);
$billing_history = new BillingHistoryModel();
$service_price = new BillingServicePriceModel();
$billing_info = new BillingInformationModel();
$billing_service = new BillingServiceModel();

/**
 *  내 구독 목록
 * 조회성공시 배열, 조회실패시 false 반환
 * @param $request_data
 * @return array|false
 */
function get_myservice($request_data)
{
    if(!is_array($request_data)){
        return false;
    }

    global $service_price, $billing_info, $billing_service;

    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $request_data['mb_id'] = $mb_id;
    $billing_info_result = $billing_info->selectList($request_data);
    if(empty($billing_info_result)){
        return false;
    }

    $results = array();
    foreach($billing_info_result as $row => $key){
        $service_id = $key['service_id'];
        $service_info = $billing_service->selectOneById($service_id);
        $current_price = array('price' =>$service_price->selectCurrentPrice($service_id));
        $results[$row]['bo_table'] = $service_info['bo_table'];
        $results[$row]['subject'] = $service_info['bo_subject'];
        $results[$row]['service'][] = $key + $service_info + $current_price;
    }

    return $results;
}

/**
 * 한 주문번호의 구독 정보를 가져옴
 * @param $od_id
 * @return array | false
 */
function get_myservice_info($od_id)
{
    global $billing_history, $billing_info, $billing_service;

    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $payment_history = $billing_history->selectOneById($od_id);
    if($payment_history['mb_id'] !== $mb_id){
        return false;
    }

    $billing_info_result = $billing_info->selectOneByOrderId($od_id);

    //가격
    $last_payemnt = $payment_history; //마지막이 첫번째로 오게되어있음.
    $billing_info_result['price'] = $last_payemnt['amount'];

    //게시판 정보 가져오기
    $service_id = $billing_info_result['service_id'];
    $service_info = $billing_service->selectOneById($service_id);
    $billing_info_result['bo_subject'] = $service_info['bo_subject'];

    return $billing_info_result;

}
/**
 * 내 구독 서비스 결제정보
 * @param $od_id
 * @return array|false
 */
function get_myservice_history($od_id)
{
    global $billing_history, $service_price, $billing_info, $billing_service;
    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $payments = $billing_history->selectListByOrderId($od_id); //TODO 페이지 필요.
    if(empty($payments)){
        return false;
    }

    return $payments;
}

/**
 * 구독 취소
 * @param string|int $od_id 구독서비스 주문번호
 * @return bool
 */
function cancel_myservice($od_id)
{
    global $billing, $billing_info;

    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $bill_info = $billing_info->selectOneByOrderId($od_id);

    if(empty($bill_info) || $bill_info['mb_id'] !== $mb_id){
        return false;
    }

    $old_billing_key =$bill_info['billing_key'];
        $batchDelResult = json_decode($billing->pg->requestDeleteBillKey($old_billing_key), true);
    if ($batchDelResult === false || !array_key_exists('result_code', $batchDelResult)) {
        return false;
    }

    if ($batchDelResult['result_code'] !== "0000") {
        return $batchDelResult;
    }

    $result = $billing_info->updateStatus($od_id, (int)false);
    if ($result) {
        return true;
    }

    return false;
}

/**
 * 사용자아이디 가져오는 함수
 * @return false|string
 */
function get_user_id()
{
    global $config, $is_guest, $is_admin;
    if ($is_guest) {
        return false;
    }

    if ($is_admin === 'super') {
        return $config['cf_admin'];
    }

    /**
     * @todo 안되는 조건찾기
     */
    $mb_id = get_session('mb_id');
    if (empty($mb_id)) {
        return false;
    }

    return $mb_id;
}