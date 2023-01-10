<?php
//로그인 상태인지 확인후 토큰으로 사용할 id를 리턴
include_once('./_common.php');

if($is_admin || $is_member && isset($member['mb_id'])) {
    $response = array(
        'result' => 'login',
        'token' => $member['mb_id']
    );

} else {
    $response = array(
        'result' => 'logout',
        'token' => ''
    );
}

echo json_encode($response);