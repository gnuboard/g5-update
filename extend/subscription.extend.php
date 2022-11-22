<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once(G5_BBS_PATH . '/subscription/subscription_service.php');

//인증
if($is_admin !== 'super' && $is_guest !== false || G5_DEBUG === true){
    checkRoute($_SERVER['REQUEST_URI'], $bo_table);
}
