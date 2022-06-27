<?php
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}

add_replace('admin_menu', 'add_admin_menu_update', 1, 1);

function add_admin_menu_update($menu) {

    $menu_num = '100600';

    if(! isset($menu['menu100']) ) return $menu;

    $add_menu = array(
        $menu_num,
        '버전 업데이트',
        G5_ADMIN_URL.'/update/',
        'update'
        );
    
    array_push($menu['menu100'], $add_menu);

    return $menu;
}