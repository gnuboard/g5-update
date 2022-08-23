<?php
include_once('./_common.php');

$it_id = isset($_REQUEST['it_id']) ? safe_replace_regex($_REQUEST['it_id'], 'it_id') : '';

if( !isset($it) && !get_session("ss_tv_idx") ){
    if( !headers_sent() ){  //헤더를 보내기 전이면 검색엔진에서 제외합니다.
        echo '<meta name="robots" content="noindex, nofollow">';
    }
    /*
    if( !G5_IS_MOBILE ){    //PC 에서는 검색엔진 화면에 노출하지 않도록 수정
        return;
    }
    */
}

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/itemuse.php');
    return;
}

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$itemuse_list = G5_SHOP_URL."/itemuselist.php";
$itemuse_form = G5_SHOP_URL."/itemuseform.php?it_id=".$it_id;
$itemuse_formupdate = G5_SHOP_URL."/itemuseformupdate.php?it_id=".$it_id;
$itemuse_url = G5_SHOP_URL."/itemuse.php?it_id=" . $it_id;

$sql_common = " FROM `{$g5['g5_shop_item_use_table']}`";
$sql_where = " WHERE it_id = '{$it_id}' AND is_confirm = '1'";
$sql_order = "";

// 컬럼체크
$row = sql_fetch("SHOW COLUMNS FROM `{$g5['g5_shop_item_use_table']}` LIKE 'ct_id'");
if (!$row) {
    sql_query("ALTER TABLE `{$g5['g5_shop_item_use_table']}` ADD COLUMN `ct_id` int(11) NOT NULL DEFAULT 0", true);
}

// 평점
$it_use = sql_fetch("SELECT it_use_cnt, it_use_avg from {$g5['g5_shop_item_table']} WHERE it_id = '{$it_id}'");
$it_use_avg = $it_use['it_use_avg'];
$star_score = get_star($it_use_avg);

// 검색조건
if ($_REQUEST['only_photo'] == "1") {
    $sql_where .= " AND is_content LIKE '%<img %'";
}

//정렬 조건
$sort = isset($_REQUEST['item_use_sort']) ? $_REQUEST['item_use_sort'] : "new";
switch ($sort) {
    case "is_score_asc":
        $sql_order = "ORDER BY is_score ASC, is_id DESC";
        break;
    case "is_score_desc":
        $sql_order = "ORDER BY is_score DESC, is_id DESC";
        break;
    default :
        $sql_order = "ORDER BY is_id DESC";
        break;
}

// 테이블의 전체 레코드수만 얻음
$sql = "SELECT COUNT(*) AS cnt " . $sql_common . $sql_where;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 5;
$total_page  = ceil($total_count / $rows); // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 레코드 구함

$sql = "SELECT 
            *,
            (SELECT ct_option FROM g5_shop_cart WHERE ct_id = {$g5['g5_shop_item_use_table']}.ct_id) AS ct_option
        $sql_common
        $sql_where
        $sql_order
        LIMIT $from_record, $rows";
$result = sql_query($sql);

$itemuse_skin = G5_SHOP_SKIN_PATH.'/itemuse.skin.php';

if (!file_exists($itemuse_skin)) {
    echo str_replace(G5_PATH.'/', '', $itemuse_skin).' 스킨 파일이 존재하지 않습니다.';
} else {
    include_once($itemuse_skin);
}