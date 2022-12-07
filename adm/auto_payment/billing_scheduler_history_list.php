<?php
$sub_menu = '400940';
$pg_code = 'kcp';
include_once './_common.php';
require_once G5_LIB_PATH . '/billing/kcp/config.php';
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '자동결제 실행기록';
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';

/* 변수 선언 */
$billing        = new Billing($pg_code);
$scheduler_model = new BillingSchedulerHistoryModel();

$history_list   = array();
$stx        = !empty($stx) ? clean_xss_tags($stx) : '';
$sst        = !empty($sst) ? clean_xss_tags($sst) : 'start_time';
$sod        = !empty($sod) ? clean_xss_tags($sod) : 'desc';
$sdate      = (isset($_GET['sdate']) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['sdate'])) ? $_GET['sdate'] : '';
$edate      = (isset($_GET['edate']) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['edate'])) ? $_GET['edate'] : '';

$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

$request_data = array(
    "stx"   => $stx,
    "sst"   => $sst,
    "sod"   => $sod,
    "sdate" => $sdate,
    "edate" => $edate
);

/* 데이터 출력 */
// 전체 건수
$total_count    = $scheduler_model->selectTotalCount($request_data);   
$total_page     = ceil($total_count / $rows);   // 전체 페이지
$request_data['offset'] = ($page - 1) * $rows;  // 시작 열
$request_data['rows']   = $rows;

// 목록 조회
$history_list = $scheduler_model->selectList($request_data);

foreach ($history_list as $i => $history) {
    $history_list[$i]['bg_class']           = 'bg' . ($i % 2);
    switch ($history['state']) {
        case 1 :
            $history_list[$i]['display_state'] = "성공";
            break;        
        case 0 :
            $history_list[$i]['display_state'] = "실패";
            break;        
        case -1 :
            $history_list[$i]['display_state'] = "부분 실패";
            break;        
    }
    $history_list[$i]['start_time_ymd'] = date('Y-m-d', strtotime($history['start_time']));
}

$qstr = $qstr . '&amp;page=' . $page;
?>

<div class="local_ov01 local_ov">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>" class="ov_listall">전체목록</a>
    <span class="btn_ov01"><span class="ov_txt">검색된 건수</span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
</div>

<form name="flist" class="local_sch01 local_sch" autocomplete="off">
    <label for="sfl" class="sound_only">검색 날짜</label>
    검색 날짜
    <input type="text" id="sdate" name="sdate" value="<?php echo $sdate ?>" class="frm_input" size="10" maxlength="10">
    ~
    <input type="text" id="edate" name="edate" value="<?php echo $edate ?>" class="frm_input" size="10" maxlength="10">
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
                    <th scope="col">
                        <label for="chkall" class="sound_only">상품 전체</label>
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">실행 결과</th>
                    <th scope="col">실행 시각</th>
                    <th scope="col">성공 건수</th>
                    <th scope="col">실패 건수</th>
                    <th scope="col">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history_list as $key => $history) { ?>
                <tr class="<?php echo $history['bg_class']; ?>">
                    <td class="td_chk2">
                        <input type="checkbox" name="chk[]" value="<?php echo $key ?>" id="chk_<?php echo $key; ?>">
                        <input type="hidden" name="id[<?php echo $key; ?>]" value="<?php echo $history['id']?>">
                    </td>
                    <td class="td_mng_l">
                        <label class="sound_only"><?php echo $history['display_state']; ?></label>
                        <?php echo $history['display_state']; ?>
                    </td>
                    <td><?php echo $history['start_time']; ?></td>
                    <td><?php echo number_format($history['success_count']); ?></td>
                    <td><?php echo number_format($history['fail_count']); ?></td>
                    <td class="td_mng_l">
                        <a href="./billing_history_list.php?date=<?php echo $history['start_time_ymd']; ?>" class="btn btn_03">
                            결제이력 확인
                        </a>
                    </td>
                </tr>
            <?php
                }
                if ($total_count == 0) {
                    echo '<tr><td colspan="6" class="empty_table">자료가 없습니다.</td></tr>';
                }
            ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <input type="submit" name="act_button" value="자동결제 수동실행" onclick="document.pressed=this.value" class="btn btn_01">
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
    $(function(){
        $("#sdate, #edate").datepicker({
            changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99", maxDate: "+0d"
        });
    })

    function check_form_scheduler_history(f)
    {
        if (confirm("자동결제를 실행하시겠습니까?")) {
            // @todo ajax 추가
            alert("결제처리 완료");
            return true;
        } else {
            return false;
        }
    }
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
