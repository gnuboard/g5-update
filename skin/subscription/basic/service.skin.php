<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once '../../../common.php';
require_once G5_PATH . '/head.php';
require_once G5_PATH . '/head.sub.php';

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head

$convertYMDUnit1 = array('y' => '연간', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');

$page_no = isset($page_no) ? $page_no : 0;
$page_per_count = isset($page_per) ? $page_per : 10;
$bo_table = isset($_GET['bo_table']) ? clean_xss_tags($_GET['bo_table']) : '';

$board_list = showServiceList($page_no, $page_per_count, $bo_table);

function convertDayWeekUnit($days)
{
    // $data = '';
    // switch ($days) {
    //     case 7 :
    //         $data = '1주';
    //         break;
    //     case '14' :
    //         $data = '2주';
    //         break;
    //     case '21' :
    //         $data = '3주';
    //         break;
    //     case '28' :
    //         $data = '4주';
    //         break;
    // }

    // return $data;
    if (is_int($days) && $days % 7 === 0) {
        return ($days / 7) . '주';
    } else {
        return $days;
    }
}

?>
<style>
    .header_title {
        display: flex;
        font-size: 16px;
        margin-bottom: 20px;
        justify-content: space-between;
    }

    .my_service_btn {
        border: 1px solid #0f192a;
        border-radius: 10px;
        font-size: 12px;
        padding: 5px;
    }

    .item_box {
        width: 100%;
        background-color: #FBFBFB;
        border: 1px solid #0f192a;
        margin: 10px;
        border-radius: 10px;
        padding: 10px;
        border-spacing: 10px;
    }

    .item_box li {
        margin-top: 1px;
        margin-bottom: 1px;
    }
    .item_box .title_area {
        display:table-cell;
        width:400px;
        border-right: 1px gray solid;
    }
    .item_box .price_area {
        display:table-cell;
        width:300px;
    }
    .item_box .price_area .price {
        font-size: 16px;
        font-weight: bold;
    }
    .item_box .button_area {
        display:table-cell;
        width:200px;
        text-align:center;
    }
    
    .service_list_wrap {
        flex-direction: row;
        width: 100%;
        background-color: #FFFFFF;
    }

    .service_name {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 0.4rem;
    }
    .service_summary {
        font-size: 14px;
    }

    .event_banner {
        display: flex;
        width : 100%;
        height : auto;
        margin-bottom: 10px;
        padding: 10px;
        height:100px;
        background-color: lightblue;
    }
</style>

<div class="header_title">
    <h2>이용권 구매</h2>
    <div class="my_service_btn"><a href="mypage.skin.php">사용 중인 구독서비스</a></div>
</div>

<div class="event_banner">
    이벤트 배너 영역
</div>

<article class="service_list_wrap">
<?php
    foreach ($board_list as $board) {
?>
    <h2 style="font-size:1.4rem;"><?php echo $board['subject'] ?></h2>
    
    <?php foreach($board['service'] as $service) { ?>
    <div class="item_box">
        <ul>
            <li class="title_area">
                <a href="service_detail.skin.php?service_id=<?php echo $service['service_id'] ?>">
                    <div class="service_name"><?= $service['service_name'] ?></div>
                    <div class="service_summary"><?= $service['service_summary'] ?></div>
                </a>
            </li>
            <li class="price_area">
                <div class="price"><?=$convertYMDUnit1[$service['recurring_unit']] ?> <?= number_format($service['price']) ?>원</div>
                <div><?=$service['service_expiration']?><?=$convertYMDUnit2[$service['service_expiration_unit']] ?> 동안 이용가능</div>
            </li>
            <li class="button_area">
                <button type="button" class="btn_frmline btn_payment" data-service_id=<?php echo $service['service_id']?>>구매</button>
            </li>
        </ul>
    </div>
<?php 
        }
    }
?>
</article>
<script>
$('.btn_payment').on('click', function(){
    location.href = '<?php echo G5_BBS_URL ?>/regular_payment.php?service_id=' + $(this).data('service_id');
})
</script>
<?php
include_once(G5_PATH . '/tail.php');