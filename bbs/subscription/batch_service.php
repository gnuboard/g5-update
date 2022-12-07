<?php

require_once(dirname(__FILE__) . '/../../common.php');
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

$pg_code = 'kcp';
$billing = new Billing($pg_code);
$billing_history = new BillingHistoryModel();
$service_price = new BillingServicePriceModel();
$billing_info = new BillingInformationModel();
$billing_service = new BillingServiceModel();

//TODO 오늘 실행했는지 체크

$request_data = array(
    'is_use' => '',
    'service_table' => '',
    'status' => '',
    'stx' => '',
    'sfl' => '',
    'sst' => '',
    'sod' => '',
    'offset' => '',
    'rows' => ''
);

$billing_list_length = $billing_info->selectTotalCount($request_data);

$request_data['service_table'] = '';
$request_data['date'] = G5_TIME_YMD;
$request_data['status'] = 1;

$start_page = 0;
$page_rows = 1000;
$billing_total_page_length = ceil($billing_list_length / $page_rows);
$currency = 410; //TODO kcp 원화 코드
for ($idx = 0; $idx < $billing_total_page_length; $idx++) {
    $request_data['rows'] = $page_rows;
    $request_data['offset'] = $idx;

    $billing_list = $billing_info->selectList($request_data);
    if (!is_array($billing_list) || count($billing_list) === 0) {
        continue;
    }

    foreach ($billing_list as $today_payment) {
        $service_id = $today_payment['service_id'];
        $price = $service_price->selectCurrentPrice($service_id);
        $price = empty($price) ? $price : 0;
        $billing_response = $billing->pg->requestBilling(array(
            'amount' => $price,
            'billing_key' => $today_payment['billing_key'],
            'cust_ip' => '',
            'od_id' => $today_payment['od_id'],
            'mb_id' => $today_payment['mb_id'],
            'mb_email' => isset($today_payment['mb_email']) ? $today_payment['mb_email'] : '',
            'mb_hp' => isset($today_payment['mb_hp']) ? $today_payment['mb_hp'] : '',
            'mb_name' => $today_payment['mb_name'],
            'name' => $today_payment['name'],
            'currency' => $currency
        ));

        if (array_key_exists('http_code', $today_payment)) {
            //통신 실패
            $history_data = array(
                'od_id' => $today_payment['od_id'],
                'mb_id' => $today_payment['mb_id'],
                'billing_key' => $today_payment['billing_key'],
                'amount' => $price,
                'result_code' => 0,
                'result_message' => '',
                'result_data' => json_encode($billing_response),
                'card_name' => '',
                'payment_count' => 0, // n 회차
                'payment_no' => 0,
                'payment_date' => G5_TIME_YMD,
                'expiration_date' => '',
            );
        } else {
            $billing_response = $billing->convertPgDataToCommonData($billing_response);

            if ($billing_response['result_code'] === '0000') {
                $history_data = array(
                    'od_id' => $today_payment['od_id'],
                    'mb_id' => $today_payment['mb_id'],
                    'billing_key' => $today_payment['billing_key'],
                    'amount' => $price,
                    'result_code' => $billing_response['result_code'],
                    'result_message' => $billing_response['result_message'],
                    'result_data' => json_encode($billing_response),
                    'card_name' => $billing_response['card_name'],
                    'payment_count' => $billing_response['payment_count'],
                    'payment_no' => $billing_response['payment_no'],
                    'payment_date' => $billing_response['payment_date'],
                    'expiration_date' => $billing_response['expiration_date'],
                );
            } else {
                $history_data = array(
                    'od_id' => $today_payment['od_id'],
                    'mb_id' => $today_payment['mb_id'],
                    'billing_key' => $today_payment['billing_key'],
                    'amount' => $price,
                    'result_code' => $billing_response['result_code'],
                    'result_message' => $billing_response['result_message'],
                    'result_data' => json_encode($billing_response),
                    'card_name' => '',
                    'payment_count' => 0,
                    'payment_no' => '',
                    'payment_date' => G5_TIME_YMD,
                    'expiration_date' => '',
                );
            }

            // 성공, 실패 모두 기록
            $billing_history->insert($history_data);
        }

        set_time_limit(20);//PHP 스크립트시간 연장
    }
}