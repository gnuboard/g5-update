<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// Tosspayments 공통 설정
require_once(G5_SHOP_PATH.'/settle_toss.inc.php');

/**
 * 결제 승인 API 호출
 */
$data = ['paymentKey' => $paymentKey, 'orderId' => $orderId, 'amount' => $amount];

$curlHandle = curl_init($paymentsUrl);

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

    $tno        = $responseJson->paymentKey;
    $amount     = $responseJson->totalAmount;
    $app_time   = $responseJson->approvedAt;

    $escw_yn    = $responseJson->useEscrow ? 'Y' : 'N';
    $pay_type   = $responseJson->method;

    $bank_name = '';
    $bankname  = '';
    $depositor = '';
    $account   = '';
    
    $card_name  = '';
    $commid     = '';
    $mobile_no  = '';

    // 계좌이체
    if ($responseJson->method == '계좌이체') {
        if (isset($responseJson->transfer->bank)) {
            $bank_name = $bankname = $BANK_CODE_KR[$responseJson->transfer->bank];
        }
        if (isset($responseJson->virtualAccount->customerName)) {
            $depositor = $responseJson->virtualAccount->customerName;
        }
        if (isset($responseJson->virtualAccount->accountNumber)) {
            $account = $responseJson->virtualAccount->accountNumber;
        }
    }

    // 가상계좌
    if ($responseJson->method == '가상계좌') {
        // callback 응답을 위한 secret값 저장
        if (isset($responseJson->secret)) {
            $app_no = $responseJson->secret;
        }
        if (isset($responseJson->virtualAccount->bank)) {
            $bank_name = $bankname = $BANK_CODE_KR[$responseJson->virtualAccount->bank];
        }
        if (isset($responseJson->virtualAccount->accountNumber)) {
            $account = $responseJson->virtualAccount->accountNumber;
        }
        if (isset($responseJson->virtualAccount->dueDate)) {
            $dueDate = date('Y-m-d H:i', strtotime($responseJson->virtualAccount->dueDate));
            $account .= ' (' . $dueDate . "까지)";
        }
    }

    // 카드결제 & 간편결제
    if ($responseJson->method == '카드' || $responseJson->method == '간편결제') {
        if (isset($responseJson->card->company)) {
            $card_name = $CARD_CODE_KR[$responseJson->card->company];
        }
    }

    // 휴대폰 결제
    if ($responseJson->method == '휴대폰') {
        if (isset($responseJson->mobilePhone->customerMobilePhone)) {
            $mobile_no = $responseJson->mobilePhone->customerMobilePhone;
        }
    }

    // 현금영수증 발급 체크
    // if (isset($responseJson->receipt->url)) {
        
    // }
} else {
    alert("[{$responseJson->code}] " . $responseJson->message );
}