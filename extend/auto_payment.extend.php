<?php
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}

add_replace('admin_menu', 'add_admin_menu_auto_payment', 1, 1);

function add_admin_menu_auto_payment($menu)
{
    if (!isset($menu['menu400'])) {
        return $menu;
    }

    array_push($menu['menu400'], array('400900', '====자동결제====', ''));
    array_push($menu['menu400'], array('400910', '구독 현황', G5_ADMIN_URL.'/auto_payment/index.php', 'auto_payment_statistics'));
    array_push($menu['menu400'], array('400920', '구독상품 관리', G5_ADMIN_URL.'/auto_payment/service_list.php', 'auto_payment_service'));
    array_push($menu['menu400'], array('400930', '구독결제 관리', G5_ADMIN_URL.'/auto_payment/billing_list.php', 'auto_payment_list'));
    array_push($menu['menu400'], array('400940', '자동결제 실행기록 ', G5_ADMIN_URL.'/auto_payment/billing_scheduler_history_list.php', 'auto_payment_scheduler'));

    return $menu;
}