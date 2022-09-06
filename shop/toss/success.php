<?php
include_once './_common.php';
include_once '../settle_toss.inc.php';

// Tosspayments 테스트용 선언
$default['de_pg_service'] = 'toss';

/**
 * @since 22.08.30
 * @todo 현금영수증 발급 시, 응답 변수 중 receiptKey를 저장해야한다. (주문취소시 현금영수증도 취소할 때 사용). [g5_shop_order > od_casseqno]
 * @see 결제취소를 하기위해서 paymentKey를 저장한다. [g5_shop_order > od_tno]
 * @see 가상계좌 웹훅 처리를 통해 입금내역을 업데이트를 하기위해 secret 값을 저장한다. [g5_shop_oder > od_app_no (varchar(20) => varchar(100))]
 */
$request_paymentKey = isset($_GET['paymentKey']) ? clean_xss_tags($_GET['paymentKey']) : "";
$request_orderId    = isset($_GET['orderId']) ? clean_xss_tags($_GET['orderId']) : "";
$request_amount     = isset($_GET['amount']) ? clean_xss_tags($_GET['amount']) : 0;

// 주문번호 체크
if (!$request_orderId) {
    alert("주문번호가 없습니다.");
}

// 주문 임시데이터 체크
$row = sql_fetch("SELECT * FROM {$g5['g5_shop_order_data_table']} WHERE od_id = '{$request_orderId}' ");
if (empty($row)) {
    alert("임시 주문정보가 저장되지 않았습니다.");
}

/**
 * 결제 금액이 같은지 검증하기
 * 결제창을 열 때 requestPayment 메서드에 담아 보냈던 amount 값과 리다이렉트 URL에 있는 실제 결제 금액인 amount 값이 같은지 확인해보세요.
 */
$order_data = unserialize(base64_decode($row['dt_data']));

$total_amount = ($order_data['org_od_price'] + $order_data['od_send_cost'] + $order_data['od_send_cost2'])
                - ($order_data['item_coupon'] + $order_data['od_coupon'] + $order_data['od_send_coupon'] + $order_data['od_temp_point']);
if ((int)$request_amount !== (int)$total_amount) {
    alert("결제요청 금액이 일치하지 않습니다.");
}

/**
 * 결제 승인 API 호출
 */
$url = 'https://api.tosspayments.com/v1/payments/' . $paymentKey;

$data = ['orderId' => $orderId, 'amount' => $amount];

$credential = base64_encode($secretKey . ':');

$curlHandle = curl_init($url);

curl_setopt_array($curlHandle, [
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credential,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($curlHandle);

$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
$isSuccess = $httpCode == 200;
$responseJson = json_decode($response);

/**
 * 결제데이터 처리 및 결과 전송
 */
if ($isSuccess) { 
    // echo json_encode($responseJson, JSON_UNESCAPED_UNICODE);
    // exit;

    // 결제취소를 위한 paymentKey 저장
    if (isset($request_paymentKey)) {
        $tno = $request_paymentKey;
    }
    // 가상계좌 결제 시 callback 응답을 위한 secret값 저장
    if ($responseJson->method == '가상계좌' && isset($responseJson->secret)) {
        $od_app_no = $responseJson->secret;
    }

    $params = array();
    $var_datas = array();

    foreach ($order_data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $_POST[$key][$k] = $params[$key][$k] = clean_xss_tags(strip_tags($v));
            }
        } else {
            if (in_array($key, array('od_memo'))) {
                $_POST[$key] = $params[$key] = clean_xss_tags(strip_tags($value), 0, 0, 0, 0);
            } else {
                $_POST[$key] = $params[$key] = clean_xss_tags(strip_tags($value));
            }
        }
    }

    // 개인결제
    if (isset($order_data['pp_id']) && $order_data['pp_id']) {
        foreach ($params as $key => $value){
            if (in_array($key, array('pp_name', 'pp_email', 'pp_hp', 'pp_settle_case'))) {
                $var_datas[$key] = $value;
                $$key = $value;
            }
        }
        include_once(G5_SHOP_PATH.'/personalpayformupdate.php');

    } else {    //상점주문
        foreach ($params as $key => $value){
            if (in_array($key, array('od_price', 'od_name', 'od_tel', 'od_hp', 'od_email', 'od_memo', 'od_settle_case', 'max_temp_point', 'od_temp_point', 'od_bank_account', 'od_deposit_name', 'od_test', 'od_ip', 'od_zip', 'od_addr1', 'od_addr2', 'od_addr3', 'od_addr_jibeon', 'od_b_name', 'od_b_tel', 'od_b_hp', 'od_b_addr1', 'od_b_addr2', 'od_b_addr3', 'od_b_addr_jibeon', 'od_b_zip', 'od_send_cost', 'od_send_cost2', 'od_hope_date'))) {
                $var_datas[$key] = $value;
                $$key = $value;
            }
        }

        $od_send_cost = (int) $_POST['od_send_cost'];
        $od_send_cost2 = (int) $_POST['od_send_cost2'];

        include_once(G5_SHOP_PATH . '/orderformupdate.php');
    }
} else {
    alert("[{$responseJson->code}] " . $responseJson->message );
}
