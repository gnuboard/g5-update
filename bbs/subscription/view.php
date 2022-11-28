<?php
//서비스와 서비스 상세 view 를 담당.
require_once dirname(__FILE__) . '/_common.php';

if (!isset($page) || $page === 0) {
    $page = 1;
}

//테마기준

if (G5_IS_MOBILE) {
    $service_theme_base_path = G5_THEME_MOBILE_PATH . '/skin/subscription';
} else {
    $service_theme_base_path = G5_THEME_PATH . '/skin/subscription';
}

$service_skin_path = $service_theme_base_path . '/service.skin.php';
$service_detail_skin_path = $service_theme_base_path . '/service_detail.skin.php';

if(isset($service_id) && check_string($service_id, G5_NUMERIC)){
    include_once ($service_detail_skin_path);
} else {
    include_once ($service_skin_path);
}
