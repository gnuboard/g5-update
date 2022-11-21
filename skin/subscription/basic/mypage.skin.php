<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once '../../../common.php';
require_once G5_PATH . '/head.php';
require_once G5_PATH . '/head.sub.php';

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head
require_once(G5_BBS_PATH . '/subscription/mypage.php'); //TODO subscription head

if (empty($is_member)) {
    alert('로그인 하셔야 됩니다.', G5_BBS_URL . '/login.php');
}

$convertYMDUnit1 = array('y' => '연간', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');

$page_no = isset($page_no) ? $page_no : 0;
$page_per_count = isset($page_per) ? $page_per : 10;

$board_list = showMyServiceList();

$title = '구독 중인 서비스 목록';
function convertYMDUnit($unit)
{
    $data = '';
    switch (strtolower($unit)) {
        case 'y' :
            $data = '년';
            break;
        case 'm' :
            $data = '월';
            break;
        case 'd' :
            $data = '일';
            break;
    }

    return $data;
}

function convertDayWeekUnit($days)
{
    $data = '';
    switch ($days) {
        case 7 :
            $data = '1주';
            break;
        case '14' :
            $data = '2주';
            break;
        case '21' :
            $data = '3주';
            break;
        case '28' :
            $data = '4주';
            break;
    }

    return $data;
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
</style>

<div class="header_title">
    <h2><?= $title ?></h2>
    <div class="my_service_btn"><a href="mypage.skin.php">구독 마이페이지</a></div>
</div>

<article class="service_list_wrap">
<?php
    foreach ($board_list as $board) {
?>
    <h2 style="font-size:1.4rem;"><?php echo $board['subject'] ?></h2>
    
    <?php foreach($board['service'] as $service) { ?>
    <div class="item_box">
        <!-- <a href="service_detail.skin.php?service_id=<?php echo $service['service_id'] ?>"> -->
            <ul>
                <li class="title_area">
                    <div class="service_name"><?= $service['service_name'] ?></div>
                    <div class="service_summary"><?= $service['service_summary'] ?></div>
                </li>
                <li class="price_area">
                    <div class="price"><?=$convertYMDUnit1[$service['recurring_unit']] ?> <?= number_format($service['price']) ?>원</div>
                    <div><?=$service['service_expiration']?><?=$convertYMDUnit2[$service['service_expiration_unit']] ?> 동안 이용가능</div>
                    <div>다음 결제일: <?php echo date('Y-m-d', strtotime($service['next_payment_date'])) ?></div>
                </li>
                <li class="button_area">
                    <button type="button" class="btn_frmline btn_cancel" data-od_id="<?= $service['od_id'] ?>">구독 취소</button>
                </li>

            </ul>
        <!-- </a> -->
    </div>
<?php 
        }
    }
?>
<script>
$(function(){
    $('.btn_cancel').on('click', function(){
        if(confirm("해당 서비스의 구독을 취소하시겠습니까?")) {
            let od_id = $(this).data('od_id');
            const data = {
                'w': 'service_cancel',
                'od_id': od_id
            };
            $.ajax(g5_bbs_url + '/subscription/ajax.mypage.php', {
                type: 'post',
                data: JSON.stringify(data),
                contentType: false,
                success: function (data) {
                    console.log(data);
                    if (data === true) {
                        alert('해당상품의 구독이 취소되었습니다.');
                        location.reload();
                    } else {
                        alert('구독취소 오류');
                    }
                    // let result = JSON.parse(data)
                    // if (res_cd == '0000') {
                    //     alert('해당상품의 구독이 취소되었습니다.');
                    // }
                },
                error: function (e) {

                }
            })
        }
    })
});
</script>
<?php
include_once(G5_PATH . '/tail.php');
