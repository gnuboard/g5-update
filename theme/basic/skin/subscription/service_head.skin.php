<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once G5_PATH . '/head.php';
require_once G5_PATH . '/head.sub.php';
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
require_once G5_BBS_PATH . '/subscription/subscription_service.php';
$autoload = new G5AutoLoader();
$autoload->register();

global $is_admin;

if (!isset($page) || empty($page)) {
    $page = 1;
}
$page_rows = 10;
$start_page = $page * $page_rows;
if($page == 1){
    $start_page = 0;
}

add_stylesheet('<link rel="stylesheet" href="'. G5_THEME_CSS_URL .'/subscription/style.css">', 1);
?>

<div class="header_title">
    <h2>서비스 안내</h2>
    <?php if((isset($member['mb_id']) && $member['mb_id']) || $is_admin){ ?>
       <div class="my_service_btn"><a href="mypage_view.php">사용 중인 구독서비스</a></div>
   <?php } else {?>
    <div class="service_btn"><a href="<?php echo G5_BBS_URL . '/subscription/view.php' ?>">구독서비스 목록</a></div>
    <?php }?>
</div>
