<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once('../../../common.php');
require_once('../../../head.php');
require_once('../../../head.sub.php');

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head
global $is_admin;

$service_id = isset($service_id) ? clean_xss_tags($service_id) : '';  // 구독 서비스 ID

if(empty($service_id)) {
    alert('해당 서비스가 없습니다.');
}
$serviceInfo = showServiceDetail($service_id);

if(empty($serviceInfo)){
    alert('해당 서비스가 없습니다.');
}
$serviceInfo = $serviceInfo[0];
$convertYMDUnit = array('y' => '년', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');
?>

<style>
    .header_title {
        display: flex;
        font-size: 16px;
        margin-bottom: 20px;
        justify-content: space-between;
    }

    .service_btn {
        border: 1px solid #0f192a;
        border-radius: 10px;
        font-size: 12px;
        padding: 5px;
    }

    .item_box {
        width: 100%;
        background-color: #FBFBFB;
        border: 1px solid #0f192a;
        margin: 10px 0;
        border-radius: 10px;
        padding: 10px;
        border-spacing: 10px;
    }

    .remove_border {
        border: none
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
        background: #3a8afd;
        margin-bottom: 10px;
    }

    .service_info_image {
        display: flex;
        background: #f6f6f6;
        width: 100%;
        height: 500px;
    }

    .section {
        margin: 20px 10px;
    }
</style>

    <div class="header_title">
        <h2>서비스 안내</h2>
        <div class="service_btn"><a href="<?php echo G5_URL . '/skin/subscription/basic/service.skin.php' ?>">구독서비스 목록</a></div>
    </div>

    <div class="item_box remove_border">
        <ul>
            <li class="title_area">
                <div class="service_name"><?= $serviceInfo['service_name'] ?></div>
                <div class="service_summary"><?= $serviceInfo['service_summary'] ?></div>
            </li>
            <li class="price_area">
                <div class="price"><?=$convertYMDUnit[$serviceInfo['recurring_unit']] ?> <?= number_format($serviceInfo['price']) ?>원 (부가세 포함)</div>
                <div><?=$serviceInfo['recurring_count']?><?=$convertYMDUnit2[$serviceInfo['recurring_unit']] ?> 마다 정기 결제</div>
            </li>
            <li class="button_area">
                <button type="button" class="btn_frmline btn_payment" data-service_id=<?php echo $serviceInfo['service_id']?>>구독</button>
            </li>
        </ul>
    </div>

    <h2>서비스 설명</h2>
    <div class="section">

        <div>
            <?php echo $serviceInfo['service_explan'] ?>
        </div>
    </div>
    <div>
        서비스 이미지영역
        <?php
            if(empty($serviceInfo)) {
                echo "<img src='{$serviceInfo['service_image']}' class='service_info_image'>";
            }
        ?>
    </div>
<script>
    $('.btn_payment').on('click', function(){
        location.href = '<?php echo G5_BBS_URL ?>/regular_payment.php?service_id=' + $(this).data('service_id');
    })
</script>
<?php
include_once(G5_PATH . '/tail.php');