<?php
$sub_menu = '800950';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '자동결제 스케쥴러 상세이력';
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';

/* 변수 선언 */
$billing        = new Billing($billing_conf['bc_pg_code']);
$history_model  = new BillingHistoryModel();

$history_list = array();
$stx    = !empty($stx) ? clean_xss_tags($stx) : '';
$sst    = !empty($sst) ? clean_xss_tags($sst) : 'id';
$sod    = !empty($sod) ? clean_xss_tags($sod) : 'desc';
$date   = (isset($_GET['date']) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['date'])) ? $_GET['date'] : '';
$date_time    = !empty($date_time) ? clean_xss_tags($date_time) : '';
$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

$request_data = array(
    "stx"   => $stx,
    "sst"   => $sst,
    "sod"   => $sod,
    "date"  => $date,
    "date_time" => $date_time
);

/* 데이터 출력 */
// 전체 건수
$total_count    = $history_model->selectTotalCountByAdmin($request_data);   
$total_page     = (int)ceil($total_count / $rows);   // 전체 페이지
$request_data['offset'] = ($page - 1) * $rows;  // 시작 열
$request_data['rows']   = $rows;

// 목록 조회
$history_list = $history_model->selectListByAdmin($request_data);

foreach ($history_list as $i => $history) {
    $count = $history['payment_count'];

    $history_list[$i]['bg_class']               = 'bg' . ($i % 2);
    $history_list[$i]['display_payment_count']  = $count . "회차";
    $history_list[$i]['display_amount']         = number_format($history['amount']) . "원";
    $history_list[$i]['display_period']         = ($history['result_code'] === "0000" ? $history['period'] : '');
    $history_list[$i]['mb_side_view']           = get_sideview($history['mb_id'], get_text($history['mb_id']) . " (" . get_text($history['mb_name']) . ")", $history['mb_email'], '');

    if ($history['total_cancel'] === $history['amount']) {
        $history_list[$i]['display_result'] = "환불";
        $history_list[$i]['display_result_color']   = "#AAAAAA";
    } else if ($history['total_cancel'] !== null) {
        $history_list[$i]['display_result'] = "부분 취소";
        $history_list[$i]['display_result_color']   = "#AAAAAA";
    } else if ($history['result_code'] === "0000"){
        $history_list[$i]['display_result'] = "성공";
        $history_list[$i]['display_result_color']   = "#53C14B";
    } else {
        $history_list[$i]['display_result'] = "실패";
        $history_list[$i]['display_result_color']   = "#FF0000";
    }
}

$qstr = $qstr . '&amp;page=' . $page;
if(!empty($date)) {
    $qstr .= '&amp;date=' . $date;
}

if(!empty($date_time)) {
    $qstr = '&amp;date_time=' . htmlspecialchars_decode($date_time);
}
echo $qstr;
?>

<div class="local_ov01 local_ov">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>" class="ov_listall">전체목록</a>
    <span class="btn_ov01"><span class="ov_txt">검색된 건수</span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
</div>

<form name="flist" class="local_sch01 local_sch" autocomplete="off">
    <label for="sfl" class="sound_only">검색 날짜</label>
    검색 날짜
    <input type="text" id="date" name="date" value="<?php echo $date ?>" class="frm_input" size="10" maxlength="10">
    <input type="submit" value="검색" class="btn_submit">
</form>

<form id="form_scheduler_history_list" name="form_scheduler_history_list" method="post" onsubmit="return check_form_scheduler_history(this)">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
                <tr>
                    <th scope="col" style="width:15%;">회원정보</th>
                    <th scope="col" style="width:15%;">주문번호</th>
                    <th scope="col" style="width:10%;">회차</th>
                    <th scope="col" style="width:15%;">결제번호</th>
                    <th scope="col" style="width:10%;">결제금액</th>
                    <th scope="col" style="width:10%;">카드명</th>
                    <th scope="col" style="width:10%;">결제상태</th>
                    <th scope="col" style="width:15%;">결제일</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history_list as $history) { ?>
                <tr class="<?php echo $history['bg_class']; ?>">
                    <td><?php echo $history['mb_side_view'] ?></td>
                    <td>
                        <a href="./billing_form.php?od_id=<?php echo $history['od_id'] ?>" target="_blank" style="text-decoration: underline;">
                            <?php echo $history['od_id'] ?>
                        </a>
                    </td>
                    <td class="td_mng_l">
                        <div><?php echo $history['display_payment_count'] ?></div>
                    </td>
                    <td>
                        <span class="payment_no" style="text-decoration: underline; cursor: pointer;" data-id="<?php echo $history['id']?>">
                            <?php echo $history['payment_no'] ?>
                        </span>
                    </td>
                    <td><?php echo $history['display_amount'] ?></td>
                    <td><?php echo $history['card_name'] ?></td>
                    <td style="color:<?php echo $history['display_result_color']?>">
                        <strong><?php echo $history['display_result'] ?></strong>
                    </td>
                    <td class="td_mng_l">
                        <?php echo $history['payment_date'] ?>
                    </td>
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

    <div class="btn_fixed_top">
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr"); ?>

<script>
    $(function(){
        $("#date").datepicker({
            changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99", maxDate: "+0d"
        });
    })
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
