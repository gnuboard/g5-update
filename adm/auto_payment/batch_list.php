<?php
$sub_menu = '400930';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '구독결제 내역';
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';

/** @todo add dbconfig.php */
$g5['batch_info_table']             = G5_TABLE_PREFIX . 'batch_info';
$g5['batch_service_table']          = G5_TABLE_PREFIX . 'batch_service';
$g5['batch_service_price_table']    = G5_TABLE_PREFIX . 'batch_service_price';
$g5['batch_service_date_table']     = G5_TABLE_PREFIX . 'batch_service_date';

$unit_array = array("y" => "년", "m" => "개월", "w" => "주", "d" => "일");
$batch_list = array();

$sfl        = !empty($sfl) ? clean_xss_tags($sfl) : "service_name";
$stx        = !empty($stx) ? clean_xss_tags($stx) : "";
$save_stx   = !empty($save_stx) ? clean_xss_tags($save_stx) : "";
$sst        = !empty($sst) ? clean_xss_tags($sst) : "start_date";
$sod        = !empty($sod) ? clean_xss_tags($sod) : "desc";

$sql_common = "";
$sql_search = "";
$sql_order  = "";

$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

/* 검색조건 */
if ($stx != "" && $sfl != "") {
    $sql_search .= "WHERE {$sfl} LIKE '%{$stx}%' ";
    if ($save_stx != $stx) {
        $page = 1;
    }
}

$sql_common .= "FROM {$g5['batch_info_table']} bi
                LEFT JOIN {$g5['batch_service_table']} bs ON bi.service_id = bs.service_id
                LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id";
$sql_common .= $sql_search;
$sql_order  .= "ORDER BY {$sst} {$sod}";

// 검색된 레코드 건수
$sql = "SELECT COUNT(*) as cnt {$sql_common}";
$row = sql_fetch($sql);
$total_count    = $row['cnt'] ? $row['cnt'] : 0;// 전체 건수
$total_page     = ceil($total_count / $rows);   // 전체 페이지
$from_record    = ($page - 1) * $rows;          // 시작 열

// 결제정보 목록 조회
$sql  = "SELECT
            bi.od_id,
            bi.start_date,
            bi.end_date,
            bi.status,
            mb.mb_id,
            mb.mb_name,
            mb.mb_email,
            bs.service_name,
            (SELECT COUNT(*) FROM g5_batch_payment bp WHERE bp.od_id = bi.od_id AND res_cd = '0000') as payment_count,
            (SELECT CONCAT(recurring_count, recurring_unit) FROM g5_batch_service_date sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS recurring,
            (SELECT price FROM g5_batch_service_price sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS price
        {$sql_common}
        {$sql_order}
        limit {$from_record}, {$rows}";
$result = sql_query($sql);

for ($i = 0; $row = sql_fetch_array($result); $i++) {
    $batch_list[$i] = $row;
    $batch_list[$i]['bg_class'] = 'bg' . ($i % 2);
    // 결제주기
    $batch_list[$i]['recurring'] = strtr($row['recurring'], $unit_array);
    // 회원정보
    $batch_list[$i]['mb_side_view'] = get_sideview($row['mb_id'], get_text($row['mb_name']), $row['mb_email'], '');
    // 주문번호에 - 추가
    switch(strlen($row['od_id'])) {
        case 16:
            $batch_list[$i]['display_od_id'] = substr($row['od_id'] ,0 ,8) . '-' . substr($row['od_id'], 8);
            break;
        default:
            $batch_list[$i]['display_od_id'] = substr($row['od_id'] , 0, 6) . '-' . substr($row['od_id'], 6);
            break;
    }
    // 기간
    $batch_list[$i]['display_date'] = $row['start_date'] . " ~ " . ($row['end_date'] != null && $row['end_date'] != '0000-00-00 00:00:00' ? $row['end_date'] : "");
    // 상태
    $batch_list[$i]['display_status'] = $row['status'] == "1" ? "진행 중" : "종료";

    // $td_color = 0;
    // if($row['od_cancel_price'] > 0) {
    //     $bg .= 'cancel';
    //     $td_color = 1;
    // }
}

$qstr = $qstr . '&amp;page=' . $page . '&amp;save_stx=' . $stx;
?>

<div class="local_ov01 local_ov">
    <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>" class="ov_listall">전체목록</a>
    <span class="btn_ov01">
        <span class="ov_txt">전체 주문내역</span>
        <span class="ov_num"> <?php echo number_format($total_count); ?>건</span>
    </span>
</div>

<form name="frmorderlist" class="local_sch03 local_sch">
    <input type="hidden" name="save_stx" value="<?php echo $stx; ?>">

    <div>
        <strong>주문상태</strong>
        <input type="radio" name="od_status" value="" id="od_status_all"    <?php echo get_checked($od_status, ''); ?>>
        <label for="od_status_all">전체</label>
        <input type="radio" name="od_status" value="주문" id="od_status_odr" <?php echo get_checked($od_status, '주문'); ?>>
        <label for="od_status_odr">주문</label>
    </div>

    <div class="sch_last">
        <strong>주문일자</strong>
        <input type="text" id="fr_date"  name="fr_date" value="<?php echo $fr_date; ?>" class="frm_input" size="10" maxlength="10"> ~
        <input type="text" id="to_date"  name="to_date" value="<?php echo $to_date; ?>" class="frm_input" size="10" maxlength="10">
        <button type="button" onclick="javascript:set_date('오늘');">오늘</button>
        <button type="button" onclick="javascript:set_date('어제');">어제</button>
        <button type="button" onclick="javascript:set_date('이번주');">이번주</button>
        <button type="button" onclick="javascript:set_date('이번달');">이번달</button>
        <button type="button" onclick="javascript:set_date('지난주');">지난주</button>
        <button type="button" onclick="javascript:set_date('지난달');">지난달</button>
        <button type="button" onclick="javascript:set_date('전체');">전체</button>
        <input type="submit" value="검색" class="btn_submit">
    </div>

    <div>
        <strong>검색</strong>
        <label for="sfl" class="sound_only">검색대상</label>
        <select name="sfl" id="sfl">
            <option value="service_name" <?php echo get_selected($sfl, 'service_name'); ?>>구독상품 명</option>
            <option value="bo_subject" <?php echo get_selected($sfl, 'bo_subject'); ?>>게시판 명</option>
        </select>
        <label for="stx" class="sound_only">검색어</label>
        <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input">
        <input type="submit" value="검색" class="btn_submit">
    </div>
</form>

<form name="form_batch_list" id="form_batch_list" onsubmit="return forderlist_submit(this);" method="post" autocomplete="off">
    <div class="tbl_head01 tbl_wrap">
        <table id="sodr_list">
            <caption>주문 내역 목록</caption>
            <thead>
            <tr>
                <th scope="col" rowspan="2">
                    <label for="chkall" class="sound_only">주문 전체</label>
                    <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                </th>
                <th scope="col" rowspan="2" id="th_od_id">주문번호</th>
                <th scope="col" colspan="3" id="th_service_name">서비스명</th>
                <th scope="col" rowspan="2" id="th_member">회원</th>
                <th scope="col" rowspan="2" id="th_date">기간</th>
                <th scope="col" rowspan="2" id="th_end_date">종료일</th>
                <th scope="col" rowspan="2">보기</th>
            </tr>
            <tr>
                <th scope="col" id="th_price">가격</th>
                <th scope="col" id="th_recurring">주기</th>
                <th scope="col" id="th_payment_count">결제횟수</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($batch_list as $key => $batch) { ?>
            <tr class="orderlist <?php echo $batch['bg_class']; ?>">
                <td rowspan="2" class="td_chk">
                    <input type="hidden" name="od_id[<?php echo $key ?>]" value="<?php echo $batch['od_id'] ?>" id="od_id_<?php echo $key ?>">
                    <label for="chk_<?php echo $i; ?>" class="sound_only">주문번호 <?php echo $batch['od_id']; ?></label>
                    <input type="checkbox" name="chk[]" value="<?php echo $key ?>" id="chk_<?php echo $key ?>">
                </td>
                <td headers="th_od_id" rowspan="2">
                    <a href="<?php echo G5_SHOP_URL; ?>/orderinquiryview.php?od_id=<?php echo $batch['od_id']; ?>" class="orderitem">
                        <?php echo $batch['display_od_id']; ?>
                    </a>
                </td>
                <td headers="th_service_name" colspan="3"><?php echo $batch['service_name']; ?></td>
                <td headers="th_member" rowspan="2"><?php echo $batch['mb_side_view']; ?></td>
                <td headers="th_date" rowspan="2"><?php echo $batch['display_date']; ?></td>
                <td headers="th_status" rowspan="2"><?php echo $batch['display_status']; ?></td>
                <td rowspan="2" class="td_mng td_mng_s">
                    <a href="./batch_form.php?od_id=<?php echo $batch['od_id']; ?>&amp;<?php echo $qstr; ?>" class="mng_mod btn btn_02">
                        <span class="sound_only"><?php echo $batch['od_id']; ?></span>
                        보기
                    </a>
                </td>
            </tr>
            <tr class="<?php echo $batch['bg_class']; ?>">
                <td><?php echo number_format($batch['price']) ?>원</td>
                <td><?php echo $batch['recurring'] ?></td>
                <td><?php echo number_format((float)$batch['payment_count']); ?>회</td>
            </tr>
            <?php
            } 
            if ($total_count == 0) {
                echo '<tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
$(function(){
    $("#fr_date, #to_date").datepicker({ 
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        showButtonPanel: true,
        yearRange: "c-99:c+99",
        maxDate: "+0d"
    });
});

function set_date(today)
{
    <?php
    $date_term = date('w', G5_SERVER_TIME);
    $week_term = $date_term + 7;
    $last_term = strtotime(date('Y-m-01', G5_SERVER_TIME));
    ?>
    if (today == "오늘") {
        document.getElementById("fr_date").value = "<?php echo G5_TIME_YMD; ?>";
        document.getElementById("to_date").value = "<?php echo G5_TIME_YMD; ?>";
    } else if (today == "어제") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
    } else if (today == "이번주") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', strtotime('-'.$date_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "이번달") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-01', G5_SERVER_TIME); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "지난주") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', strtotime('-'.$week_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', strtotime('-'.($week_term - 6).' days', G5_SERVER_TIME)); ?>";
    } else if (today == "지난달") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-01', strtotime('-1 Month', $last_term)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-t', strtotime('-1 Month', $last_term)); ?>";
    } else if (today == "전체") {
        document.getElementById("fr_date").value = "";
        document.getElementById("to_date").value = "";
    }
}

function forderlist_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
        if(confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            f.action = "./orderlistdelete.php";
            return true;
        }
        return false;
    }

    var change_status = f.od_status.value;

    if (f.od_status.checked == false) {
        alert("주문상태 변경에 체크하세요.");
        return false;
    }

    var chk = document.getElementsByName("chk[]");

    for (var i=0; i<chk.length; i++)
    {
        if (chk[i].checked)
        {
            var k = chk[i].value;
            var current_settle_case = f.elements['current_settle_case['+k+']'].value;
            var current_status = f.elements['current_status['+k+']'].value;
        }
    }

    if (!confirm("선택하신 주문서의 주문상태를 '"+change_status+"'상태로 변경하시겠습니까?"))
        return false;

    f.action = "./orderlistupdate.php";
    return true;
}
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
