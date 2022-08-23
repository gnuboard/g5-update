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

// 현재페이지, 총페이지수, 한페이지에 보여줄 행, URL
function itemuse_page($write_pages, $cur_page, $total_page, $url, $add="")
{
    //$url = preg_replace('#&amp;page=[0-9]*(&amp;page=)$#', '$1', $url);
    $url = preg_replace('#&amp;page=[0-9]*#', '', $url) . '&amp;page=';

    $str = '';
    if ($cur_page > 1) {
        $str .= '<a href="'.$url.'1'.$add.'" class="pg_page pg_start">처음</a>'.PHP_EOL;
    }

    $start_page = ( ( (int)( ($cur_page - 1 ) / $write_pages ) ) * $write_pages ) + 1;
    $end_page = $start_page + $write_pages - 1;

    if ($end_page >= $total_page) $end_page = $total_page;

    if ($start_page > 1) $str .= '<a href="'.$url.($start_page-1).$add.'" class="pg_page pg_prev">이전</a>'.PHP_EOL;

    if ($total_page > 1) {
        for ($k=$start_page;$k<=$end_page;$k++) {
            if ($cur_page != $k)
                $str .= '<a href="'.$url.$k.$add.'" class="pg_page">'.$k.'</a><span class="sound_only">페이지</span>'.PHP_EOL;
            else
                $str .= '<span class="sound_only">열린</span><strong class="pg_current">'.$k.'</strong><span class="sound_only">페이지</span>'.PHP_EOL;
        }
    }

    if ($total_page > $end_page) $str .= '<a href="'.$url.($end_page+1).$add.'" class="pg_page pg_next">다음</a>'.PHP_EOL;

    if ($cur_page < $total_page) {
        $str .= '<a href="'.$url.$total_page.$add.'" class="pg_page pg_end">맨끝</a>'.PHP_EOL;
    }

    if ($str)
        return "<nav class=\"pg_wrap\"><span class=\"pg\">{$str}</span></nav>";
    else
        return "";
}

$itemuse_list = G5_SHOP_URL."/itemuselist.php";
$itemuse_form = G5_SHOP_URL."/itemuseform.php?it_id=".$it_id;
$itemuse_formupdate = G5_SHOP_URL."/itemuseformupdate.php?it_id=".$it_id;
$itemuse_url = G5_SHOP_URL."/itemuse.php?it_id=" . $it_id;

$sql_common = " FROM `{$g5['g5_shop_item_use_table']}`";
$sql_where = " WHERE it_id = '{$it_id}' AND is_confirm = '1'";
$sql_order = "";

// 평점
$it_use = sql_fetch("SELECT it_use_cnt, it_use_avg from {$g5['g5_shop_item_table']} WHERE it_id = '{$it_id}'");
$it_use_avg = $it_use['it_use_avg'];
$star_score = get_star($it_use_avg);

// 검색조건
if ($_REQUEST['only_photo'] == "1") {
    $sql_where .= " AND is_content LIKE '%<img %'"; // 더 확실한 이미지검색조건이 있는지 확인 필요함.
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