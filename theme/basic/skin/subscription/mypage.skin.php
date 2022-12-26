<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once dirname(__FILE__) . '/mypage_head.skin.php';

$convertYMDUnit1 = $billing->getUnitArray('prefix');
$convertYMDUnit2 = $billing->getUnitArray('period');

$request_data = array(
    'sfl' => '',
    'stx' => '',
    'sst' => 'start_date',
    'sod' => 'desc',
    'status' => 1,
    'offset' => $start_page,
    'rows' => $page_rows,
    'mb_id' => get_user_id()
);
$board_list = get_myservice($request_data);
$request_data['status'] = 0;
$expiration_list = get_myservice($request_data);

$request_data['status'] = null; //status 리셋
$total_page = (int)(get_myservice_total_count($request_data) / $page_rows);
if (empty($board_list)) {
    $board_list = array();
}
if (empty($expiration_list)) {
    $expiration_list = array();
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
        display: table-cell;
        width: 400px;
        border-right: 1px gray solid;
    }

    .item_box .price_area {
        display: table-cell;
        width: 300px;
    }

    .item_box .price_area .price {
        font-size: 16px;
        font-weight: bold;
    }

    .item_box .button_area {
        display: table-cell;
        width: 200px;
        text-align: center;
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
    <h2>구독 중인 서비스 목록</h2>
</div>

<article class="service_list_wrap">
    <?php
    foreach ($board_list as $board) {
    ?>
        <h2 style="font-size:1.4rem;"><?php echo $board['subject'] ?></h2>

        <?php foreach ($board['service'] as $service) { ?>
            <div class="item_box">
                <ul>
                    <li class="title_area">
                        <a href="mypage.php?od_id=<?php echo $service['od_id'] ?>">
                            <div class="service_name"><?php echo $service['name'] ?></div>
                            <div class="service_summary"><?php echo $service['summary'] ?></div>
                        </a>
                    </li>
                    <li class="price_area">
                        <div class="price"><?php echo $convertYMDUnit1[$service['recurring_unit']] ?> <?php echo number_format($service['price']) ?>원</div>
                        <div><?php
                                if ($service['expiration'] !== 0) {
                                    echo $service['expiration'] . $convertYMDUnit2[$service['expiration_unit']] . ' 동안 이용가능';
                                }
                                ?>
                        </div>
                        <div>다음 결제일: <?php echo date('Y-m-d', strtotime($service['next_payment_date'])) ?></div>
                    </li>
                    <li class="button_area">
                        <button type="button" class="btn_frmline btn_cancel" data-od_id="<?php echo $service['od_id'] ?>">구독 취소</button>
                    </li>
                </ul>
            </div>
    <?php
        }
    }
    ?>
</article>

<div class="header_title">
    <h2>종료된 구독 서비스</h2>
</div>
<article class="service_list_wrap">
    <?php
    foreach ($expiration_list as $board) {
    ?>
        <h2 style="font-size:1.4rem;"><?php echo $board['subject'] ?></h2>

        <?php foreach ($board['service'] as $service) { ?>
            <div class="item_box">
                <ul>
                    <li class="title_area">
                        <a href="mypage.php?od_id=<?php echo $service['od_id'] ?>">
                            <div class="service_name"><?php echo $service['name'] ?></div>
                            <div class="service_summary"><?php echo $service['summary'] ?></div>
                        </a>
                    </li>
                    <li class="price_area">
                        <div class="price"><?php echo $convertYMDUnit1[$service['recurring_unit']] ?> <?php echo number_format($service['price']) ?>원</div>
                        <div>
                            <?php
                            if ($service['expiration'] !== 0) {
                                echo $service['expiration'] . $convertYMDUnit2[$service['expiration_unit']] . ' 동안 이용가능';
                            }
                            ?>
                        </div>
                        <div>다음 결제일: <?php echo date('Y-m-d', strtotime($service['next_payment_date'])) ?></div>
                    </li>
                </ul>
            </div>
    <?php
        }
    }
    ?>
</article>

<script>
    $(function() {
        $('.btn_cancel').on('click', function() {
            if (confirm("해당 서비스의 구독을 취소하시겠습니까?")) {
                let od_id = $(this).data('od_id');
                const data = {
                    'w': 'cancel',
                    'od_id': od_id
                };
                $.ajax(g5_bbs_url + '/subscription/ajax.mypage.php', {
                    type: 'post',
                    data: JSON.stringify(data),
                    contentType: false,
                    success: function(data) {
                        if (data === true) {
                            alert('해당상품의 구독이 취소되었습니다.');
                            location.reload();
                        } else {
                            alert('구독취소 오류');
                        }
                    },
                    error: function(e) {

                    }
                })
            }
        })
    });
</script>
<?php
echo get_paging(10, $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr");
include_once G5_PATH . '/tail.php';
