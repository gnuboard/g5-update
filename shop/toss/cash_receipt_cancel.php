<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!isset($receiptKey)) {
    return;
}

include_once(G5_SHOP_PATH.'/settle_toss.inc.php');

$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => "https://api.tosspayments.com/v1/cash-receipts/" . $receiptKey . "/cancel",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_HTTPHEADER => array(
    "Authorization: Basic " . $credential,
    "Content-Type: application/json"
),
));

$response_cancel = curl_exec($curl);
$err_cancel = curl_error($curl);

curl_close($curl);

if ($err_cancel) {
    $receipt_result_msg = '현금영수증 취소 요청처리가 정상적으로 완료되지 않았습니다.';
    if (!$is_admin) {
        $receipt_result_msg .= '쇼핑몰 관리자에게 문의해 주십시오.';
    }
}