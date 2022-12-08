<?php
/**
 * @deprecated /adm/admin.menu800.php로 추가되어서 해당 extend 삭제 예정
 */
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}

add_replace('admin_menu', 'add_admin_menu_auto_payment', 1, 1);

function add_admin_menu_auto_payment($menu)
{
    
    if (isset($menu['menu800'])) {
        return $menu;
    }

    $menu['menu800'] = array();
    array_push($menu['menu800'], array('800900', '정기결제 관리', G5_ADMIN_URL.'/auto_payment/index.php', 'auto_payment_statistics'));
    array_push($menu['menu800'], array('800910', '정기결제 현황&통계', G5_ADMIN_URL.'/auto_payment/index.php', 'auto_payment_statistics'));
    array_push($menu['menu800'], array('800920', '정기결제 설정', G5_ADMIN_URL.'/auto_payment/config_form.php', 'auto_payment_config'));
    array_push($menu['menu800'], array('800930', '구독상품 관리', G5_ADMIN_URL.'/auto_payment/service_list.php', 'auto_payment_service'));
    array_push($menu['menu800'], array('800940', '구독정보&결제 관리', G5_ADMIN_URL.'/auto_payment/billing_list.php', 'auto_payment_list'));
    array_push($menu['menu800'], array('800950', '자동결제 실행기록 ', G5_ADMIN_URL.'/auto_payment/billing_scheduler_history_list.php', 'auto_payment_scheduler'));

    return $menu;
}