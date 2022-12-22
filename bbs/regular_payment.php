<?php

include_once dirname(__FILE__) . '/_common.php';
require_once(G5_BBS_PATH . '/subscription/subscription_service.php');

/**
 * @var string $service_id $_POST['service_id']
 */
if ($is_guest) {
    alert('회원만 이용하실 수 있습니다.', G5_BBS_URL . '/login.php');
}
if (isset($service_id) && $service_id === '0') {
    $service_id = 0;
} else {
    $service_id = empty($service_id) ? null : $service_id;
    if ($service_id === null) {
        alert('서비스 상품이 없습니다.', G5_URL);
    }
}

$service_info = get_service_detail($service_id);
if ($service_info === null) {
    alert('해당 상품이 없습니다.', G5_URL);
}

if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH . '/skin/subscription/auto_payment_orderform.skin.php');
} else {
    include_once(G5_THEME_PATH . '/skin/subscription/auto_payment_orderform.skin.php');
}
