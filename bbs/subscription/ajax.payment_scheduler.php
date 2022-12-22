<?php

require_once(dirname(__FILE__) . '/_common.php');
require_once G5_LIB_PATH . '/billing/_setting.php';

curl_request_async(G5_BBS_URL . '/subscription/payment_scheduler.php');

/**
 * 일회성 요청
 * @param $url
 * @return void
 */
function curl_request_async($url)
{
    $headerData    = array("Content-Type: application/json", "charset=utf-8");

    // API REQ
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // API RES
    curl_exec($ch);
    if(defined(CURLINFO_HTTP_CODE)){
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
    curl_close($ch);

    $http_code = isset($http_code) ? $http_code : 200;
    response_json('결제작업이 실행되었습니다.', $http_code);

}