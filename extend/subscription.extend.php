<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once(G5_BBS_PATH . '/subscription/subscription_service.php');

//인증
/**
 * @todo version.extend.php 보다 먼저 선언되어 alert 함수 내부의 G5_CSS_VER를 사용하지 못해 오류 발생
 */
//if($is_admin !== 'super' && $is_guest !== false || G5_DEBUG === true){
//    checkRoute($_SERVER['REQUEST_URI'], $bo_table);
//}
add_event('header_board', 'checkRoute', 10, 1);

