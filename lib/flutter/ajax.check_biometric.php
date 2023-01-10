<?php
require_once '_common.php';

// $_SERVER['HTTP_REFERER']

$uuid = $_POST['uuid'];

if (is_null($uuid)) {
    echo json_encode(array('result' => false, 'message' => '실패11'));
    exit;
}

$sql = "SELECT * FROM {$g5['member_table']}_device WHERE id = '{$uuid}'";
$result = sql_fetch($sql);
if ($result) {

    $mb = get_member($result['mb_id']);

    // 회원아이디 세션 생성
    set_session('ss_mb_id', $mb['mb_id']);
    // FLASH XSS 공격에 대응하기 위하여 회원의 고유키를 생성해 놓는다. 관리자에서 검사함 - 110106
    set_session('ss_mb_key', md5($mb['mb_datetime'] . get_real_client_ip() . $_SERVER['HTTP_USER_AGENT']));
    // 회원의 토큰키를 세션에 저장한다. /common.php 에서 해당 회원의 토큰값을 검사한다.
    if(function_exists('update_auth_session_token')) update_auth_session_token($mb['mb_datetime']);

    // 아이디 쿠키에 한달간 저장
    if (isset($auto_login) && $auto_login) {
        // 3.27
        // 자동로그인 ---------------------------
        // 쿠키 한달간 저장
        $key = md5($_SERVER['SERVER_ADDR'] . $_SERVER['SERVER_SOFTWARE'] . $_SERVER['HTTP_USER_AGENT'] . $mb['mb_password']);
        set_cookie('ck_mb_id', $mb['mb_id'], 86400 * 31);
        set_cookie('ck_auto', $key, 86400 * 31);
        // 자동로그인 end ---------------------------
    } else {
        set_cookie('ck_mb_id', '', 0);
        set_cookie('ck_auto', '', 0);
    }

    $result['result'] = true;
    $result['message'] = '성공';
} else {
    $result['result'] = false;
    $result['message'] = '실패';
}

echo json_encode($result);
