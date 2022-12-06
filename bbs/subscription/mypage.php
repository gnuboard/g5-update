<?php
//마이페이지와 마이페이지 상세 view
require_once dirname(__FILE__) . '/_common.php';
include_once G5_LIB_PATH . '/billing/config.php';
if (!isset($page) || empty($page)) {
    $page = 1;
}
//테마기준

if (G5_IS_MOBILE) {
    $service_theme_base_path = G5_THEME_MOBILE_PATH . '/skin/subscription';
} else {
    $service_theme_base_path = G5_THEME_PATH . '/skin/subscription';
}

$service_skin_path = $service_theme_base_path . '/mypage.skin.php';
$service_detail_skin_path = $service_theme_base_path . '/mypage_detail.skin.php';

if(isset($od_id) && check_string($od_id, G5_NUMERIC)){
    include_once ($service_detail_skin_path);
} else {
    include_once ($service_skin_path);
}
