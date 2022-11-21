<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once('../../../common.php');
require_once('../../../head.php');
require_once('../../../head.sub.php');

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head
//add_stylesheet('<link rel="stylesheet" href="'.$visit_skin_url.'/style.css">', 0);
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
        border: 1px solid #ff63ab;
        border-radius: 10px;
        font-size: 12px;
        padding: 5px;
    }

    .item_box {
        width: 150px;
        height: 150px;
        background-color: #Fbfbfb;
        border: 1px solid #ff63ab;
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

<?php
include_once(G5_PATH . '/tail.php');