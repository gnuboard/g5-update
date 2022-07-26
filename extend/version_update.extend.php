<?php
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}

add_replace('admin_menu', 'add_admin_menu_update', 1, 1);

function add_admin_menu_update($menu)
{
    if (!isset($menu['menu100'])) {
        return $menu;
    }

    array_push($menu['menu100'], array('100600', '버전 업데이트', G5_ADMIN_URL.'/update/', 'update'));
    array_push($menu['menu100'], array('100700', '데이터베이스 업데이트', G5_ADMIN_URL.'/update/database.php', 'update_database'));

    return $menu;
}
