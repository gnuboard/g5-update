<?php
include_once "./_common.php";

$data = array();

try {
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    if ($admin_password == '') {
        throw new Exception("관리자 비밀번호가 입력되지 않았습니다.");
    }

    $admin = get_admin('super');
    if (!check_password($admin_password, $admin['mb_password'])) {
        throw new Exception("관리자 비밀번호가 일치하지 않습니다.");
    }
    
    $data['error']   = 0;
} catch (Exception $e) {
    $data['error']   = 1;
    $data['code']    = $e->getCode();
    $data['message'] = $e->getMessage();
}

die(json_encode($data));
