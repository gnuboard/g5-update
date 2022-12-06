<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once dirname(__FILE__) . '/service_head.skin.php';

$convertYMDUnit1 = array('y' => '연간', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');

$bo_table = isset($_GET['bo_table']) ? clean_xss_tags($_GET['bo_table']) : '';

$request = array(
    'is_use' => '',
    'service_table' => '',
    'stx' => '',
    'sfl' => '',
    'sst' => '',
    'sod' => '',
    'offset' => '',
    'rows' => ''
);

$request['service_table'] = $bo_table;
$request['offset'] = $start_page;
$request['rows'] = $page_rows;

$services = get_service_list($request);
?>

<div class="event_banner">
    이벤트 배너 영역
</div>

<article class="service_list_wrap">
<?php
    foreach ($services as $board) {
?>
    <h2 style="font-size:1.4rem;"><?php echo  $board['subject'] ?> </h2>
    
    <?php foreach($board['service'] as $service) { ?>
    <div class="item_box">
        <ul>
            <li class="title_area">
                <a href="view.php?service_id=<?php echo  $service['service_id'] ?>">
                    <div class="service_name"><?php echo  $service['name'] ?></div>
                    <div class="service_summary"><?php echo  $service['summary'] ?></div>
                </a>
            </li>
            <li class="price_area">
                <div class="price"><?php echo  $convertYMDUnit1[$service['recurring_unit']] ?> <?php echo  number_format($service['price']) ?>원</div>
                <div><?php echo  $service['expiration'] ?><?php echo  $convertYMDUnit2[$service['expiration_unit']] ?> 동안 이용가능</div>
            </li>
            <li class="button_area">
                <button type="button" class="btn_frmline btn_payment" data-service_id=<?php echo  $service['service_id'] ?>>구매</button>
            </li>
        </ul>
    </div>
<?php 
        }
    }
?>
</article>
<script>
    $('.btn_payment').on('click', function () {
        location.href = '<?php echo G5_BBS_URL ?>/regular_payment.php?service_id=' + $(this).data('service_id');
    })
</script>
<?php
include_once(G5_PATH . '/tail.php');