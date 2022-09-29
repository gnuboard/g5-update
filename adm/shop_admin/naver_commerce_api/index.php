<?php
$sub_menu = '400999';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '[G5-152] 네이버 커머스API 연동 페이지';
include_once (G5_ADMIN_PATH.'/admin.head.php');

/**
 * @todo AutoLoader
 */
include_once 'lib/interface/SignatureInterface.php';
include_once 'lib/SignatureGeneratorSimple.php';
include_once 'lib/SignatureGeneratorIrcmaxell.php';
include_once 'lib/CommerceApi.php';
include_once 'lib/CommerceApiAuth.php';
include_once 'lib/G5SmartstoreProduct.php';

echo "<h1 style='font-size:2em;'>인증 토큰 생성</h1>";

/**
 * @ignore 애플리케이션 변수가 외부에 노출되지 않도록 주의가 필요하다. ($client_id, $client_secret)
 */
$client_id = '';
$client_secret = '';
$commerceApiAuth = new CommerceApiAuth($client_id, $client_secret, new SignatureGeneratorSimple());
$productInstance = new G5SmartstoreProduct($commerceApiAuth);
$productInstance->getChannelProduct(7246247899);


include_once (G5_ADMIN_PATH.'/admin.tail.php');
