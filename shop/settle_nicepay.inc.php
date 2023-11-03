<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// curl 체크
if (!function_exists('curl_init')) {
    alert('cURL 모듈이 설치되어 있지 않습니다.\\n상점관리자에게 문의해 주십시오.');
}

if ($default['de_card_test']) {
    // 테스트인 경우
    $default['de_nicepay_mid'] = 'nicepay00m';
    // $default['de_nicepay_key'] = '33F49GnCMS1mFYlGXisbUDzVf2ATWCl9k3R++d5hDd3Frmuos/XLx8XhXpe+LDYAbpGKZYSwtlyyLOtS/8aD7A==';
    $default['de_nicepay_key'] = 'EYzu8jGGMfqaDEp76gSckuvnaHHu+bC4opsSN6lHv3b2lurNYkVXrZ7Z1AoqQnXI3eLuaUFyoRNC6FkrzVjceg==';
} else {
    // 실결제인 경우
    $default['de_nicepay_mid'] = "SIR".$default['de_nicepay_mid'];
}

$nicepay_returnURL = G5_SHOP_URL.'/orderformupdate.php';

// $ediDate = preg_replace('/[^0-9]/', '', G5_TIME_YMDHIS);
// $hashString = bin2hex(hash('sha256', $ediDate.$default['de_nicepay_mid'].$price.$default['de_nicepay_key'], true));

$NICEPAY_METHOD = array(
    'CARD'  => '신용카드',
    'BANK'  => '계좌이체',
    'VBANK' => '가상계좌',
    'CELLPHONE' => '휴대폰'
);

if (! function_exists('nicepay_reqPost')) {
    //Post api call
    function nicepay_reqPost($data, $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);					//connection timeout 15 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));	//POST data
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        curl_close($ch);	 
        return $response;
    }
}