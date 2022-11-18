<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once('../../../common.php');
require_once('../../../head.php');
require_once('../../../head.sub.php');

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head
global $is_admin;


$page_no = isset($page_no) ? $page_no : 0;
$page_per_count = isset($page_per) ? $page_per : 10;

$serviceList = showServiceList($page_no, $page_per_count);

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
            width: 150px;
            height: 150px;
            background-color: #Fbfbfb;
            border: 1px solid #0f192a;
            margin: 10px;
            border-radius: 10px;
            padding: 10px;
        }

        .item_box li {
            margin-top: 1px;
            margin-bottom: 1px;
        }

        .service_list_wrap {
            display: flex;
            flex-direction: row;
            width: 100%;
            background-color: #0f192a;
            height: 300px;
        }

        .service_name {
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .event_banner {
            display: flex;
            background: #3a8afd;
            margin-bottom: 10px;
        }
    </style>
    <div class="header_title">
        <h2>이용권 구독</h2>

        <div class="my_service_btn"><a href="mypage.skin.php">구독 마이페이지</a></div>
    </div>
    <div class="event_banner">
        이벤트
    </div>

    <article class="service_list_wrap">
        <?php
        foreach ($serviceList as $service) {
            ?>

            <div class="item_box">
                <a href="service_detail.skin.php?service_id=<?= $service['service_id'] ?>">
                    <ul>
                        <li><span class="service_name"><?= $service['service_name'] ?></span></li>
                        <li>소개: <?= $service['service_summary'] ?></li>
                        <li><?= convertYMDUnit($service['recurring_unit']) ?> <?= $service['price'] ?>원 부터</li>
                        <li><?php
                            echo $service['service_expiration'];
                            echo convertYMDUnit($service['service_expiration_unit']) ?> 약정
                        </li>
                    </ul>
                    <div>상품 상세보기</div>
                </a>
            </div>

            <?php
        } ?>

    </article>

<?php
include_once(G5_PATH . '/tail.php');