<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once G5_PATH . '/head.php';
require_once G5_PATH . '/head.sub.php';

if (empty($is_member)) {
    alert('로그인 하셔야 됩니다.', G5_BBS_URL . '/login.php');
}
if (!isset($page) || empty($page)) {
    $page = 1;
}
$page_rows = 10;
$start_page = $page  * $page_rows;
if($page == 1){
    $start_page = 0;
}
require_once(G5_BBS_PATH . '/subscription/subscription_service.php');
