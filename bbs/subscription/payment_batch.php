<?php

/**
 * 매일 실행되는 정기 결제
 */
require_once(dirname(__FILE__) . '/_common.php');
require_once G5_LIB_PATH . '/billing/_setting.php';
ignore_user_abort(true); // http 커넥션이 끊어져도 동작하게 설정.

$pg_code = $billing_conf['bc_pg_code'];
if(empty($pg_code)){
    exit;
}
$billing = new Billing($pg_code);
$billing_history = new BillingHistoryModel();
$service_price = new BillingServicePriceModel();
$billing_info = new BillingInformationModel();
$billing_scheduler = new BillingSchedulerHistoryModel();

$user_ip = get_real_client_ip();

$query_data = array(
    'service_table' => '',
    'status' => 1,
    'stx' => '',
    'sfl' => '',
    'sst' => '',
    'sod' => '',
    'offset' => 0,
    'rows' => 1000,
    'date' => G5_TIME_YMD,
    'sdate' => G5_TIME_YMD . ' 00:00:00',
    'edate' => G5_TIME_YMD . ' 23:59:59',
    'exclude_end_date' => G5_TIME_YMD //배치실행일이 구독 만료일이면 결제하지 않는다.
);

$success_count = 0;
$fail_count = 0;
$currency = 410; // @TODO kcp 원화 코드
$payment_success_code = '0000'; // kcp 결제 성공코드

define('STATE_SUCCESS', 1); //성공
define('STATE_PART_FAIL', -1); //일부 실패
define('STATE_FAIL', 0); // 모두 실패
define('STATE_CONTINUE', 2); // 스케쥴링 작업 실행 중
define('STATE_FORCE_STOP', 3); // 스케쥴링 작업 강제종료

$state = STATE_CONTINUE;
$scheduler_start_data = array(
    'success_count' => $success_count,
    'fail_count' => $fail_count,
    'state' => $state,
    'start_time' => G5_TIME_YMDHIS,
    'ip' => $user_ip
);

$result = $billing_scheduler->insert($scheduler_start_data);
error_log('-------new - run ---insert' . $result . PHP_EOL, 3, 'batch_result.log.txt');
$billing_list_length = $billing_info->selectTotalCount($query_data);
$billing_total_page = (int)ceil($billing_list_length / $query_data['rows']); //DB 부하, 배열 메모리 줄이기위한 페이징
for ($idx = 0; $idx < $billing_total_page; $idx++) {
    $query_data['offset'] = $query_data['rows'] * $idx;

    $billing_list = $billing_info->selectList($query_data);
    if (!is_array($billing_list) || count($billing_list) === 0) {
        continue;
    }

    foreach ($billing_list as $today_payment) {

        //이벤트 기간 체크
        if (!$billing->isNullByDate($today_payment['event_expiration_date']) &&
            strtotime($today_payment['event_expiration_date']) > strtotime(G5_TIME_YMD)) {
            $price = $today_payment['event_price'];
        } else {
            //구독만료기간이 있는 상품.
            if (!$billing->isNullByDate($today_payment['end_date'])) {
                $price = $today_payment['price'];
            } //만료기간이 설정되지 않음 상품, 변동된 가격을 따라 결제한다.
            else {
                $price = $service_price->selectCurrentPrice($today_payment['service_id']);
            }
        }

        $pg_req = array(
            'amount' => $price,
            'billing_key' => $today_payment['billing_key'],
            'cust_ip' => $user_ip,
            'od_id' => $today_payment['od_id'],
            'mb_id' => $today_payment['mb_id'],
            'mb_email' => isset($today_payment['mb_email']) ? $today_payment['mb_email'] : '',
            'mb_hp' => isset($today_payment['mb_hp']) ? $today_payment['mb_hp'] : '',
            'mb_name' => $today_payment['mb_name'],
            'name' => $today_payment['name'],
            'currency' => $currency
        );
        //결제시작
        $pg_response = $billing->pg->requestBilling($pg_req);
        $pg_response = $billing->convertPgDataToCommonData($pg_response);

        //지난 결제성공 기록불러오기
        //결제 카운트, 실패시 이전 결제만기 날짜등 필요.
        $history_data = $billing_history->selectOneLastSuccessByOdId($today_payment['od_id'], $payment_success_code);
        if (empty($history_data)) {
            ++$fail_count;
            continue;
        }

        if (isset($pg_response['result_code']) && $pg_response['result_code'] === '0000') {
            ++$success_count;

            $next_payment_date = $billing->nextPaymentDate(
                G5_TIME_YMD,
                G5_TIME_YMD,
                $today_payment['recurring'],
                $today_payment['recurring_unit']
            );

            $history_data['expiration_date'] = $next_payment_date;
            $billing_info->updateNextPaymentDate($history_data['od_id'], $next_payment_date);
        } else {
            //pg 사 빌링오류 또는 http 통신 실패 기록
            ++$fail_count;
        }

        $error_http_code = isset($pg_response['http_code']) ? $pg_response['http_code'] : 0;
        $history_data['result_code'] = isset($pg_response['result_code']) ? $pg_response['result_code'] : $error_http_code;
        $history_data['result_message'] = isset($pg_response['result_message']) ? $pg_response['result_message'] : 'pg사 연결 실패';
        $history_data['result_data'] = json_encode($pg_response);
        $history_data['amount'] = $price;
        $history_data['payment_date'] = G5_TIME_YMD;
        $history_data['card_name'] = isset($pg_response['card_name']) ? $pg_response['card_name'] : '';
        $history_data['payment_count'] += 1;

        // 성공, 실패 모두 기록
        $billing_history->insert($history_data);

        set_time_limit(4); //PHP 실행시간 연장

        // 훅스
        run_event('end_billing_per_customer', $history_data);
    }
}

if ($billing_list_length === $success_count || $billing_list_length === 0) {
    $state = STATE_SUCCESS;
} else {
    if ($success_count === 0) {
        $state = STATE_FAIL;
    } else {
        $state = STATE_PART_FAIL;
    }
}

$scheduler_result = array(
    'success_count' => $success_count,
    'fail_count' => $fail_count,
    'state' => $state
);

$update_condition = array(
    'start_time' => G5_TIME_YMDHIS,
    'ip' => $user_ip
);

$billing_scheduler->update($update_condition ,$scheduler_result);

run_event('end_batch_billing', $scheduler_result);