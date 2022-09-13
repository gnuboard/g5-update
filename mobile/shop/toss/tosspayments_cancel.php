<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/*******************************************************************
 * 7. DB연동 실패 시 강제취소                                      *
 *                                                                 *
 * 지불 결과를 DB 등에 저장하거나 기타 작업을 수행하다가 실패하는  *
 * 경우, 아래의 코드를 참조하여 이미 지불된 거래를 취소하는 코드를 *
 * 작성합니다.                                                     *
 *******************************************************************/

$cancelFlag = "true";

// $cancelFlag를 "true"로 변경하는 condition 판단은 개별적으로
// 수행하여 주십시오.

if ($cancelFlag == "true") {

    if (!isset($paymentKey)) {
        return;
    }

    include_once(G5_SHOP_PATH.'/settle_toss.inc.php');

    $curl = curl_init();

    curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.tosspayments.com/v1/payments/{$paymentKey}/cancel",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\"cancelReason\":\"{$cancel_msg}\"}",
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic dGVzdF9za19MZXg2QkpHUU9WREVZUlg5QTRRclc0dzJ6TmJnOg==",
        "Content-Type: application/json"
    ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        $pg_res_cd = $err;
        $pg_res_msg = "cURL Error";
    } else {
        $responseJson = json_decode($response);

        if (isset($responseJson->code)) {
            $pg_res_cd = $responseJson->code;
            $pg_res_msg = $responseJson->message;
        }
    }
}