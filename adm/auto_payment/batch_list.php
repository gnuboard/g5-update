<?php
$sub_menu = '400930';
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '구독결제 내역';
include_once G5_ADMIN_PATH . '/admin.head.php';

/* 변수 선언 */
$g5Mysqli = new G5Mysqli();

$unit_array     = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');
$search_list    = array('service_name', 'mb.mb_id', 'mb.mb_name');
$orderby_list   = array('service_id', 'service_name', 'price', 'service_order', 'service_use', 'start_date');
$direction_list = array('desc', 'asc');
$status_list    = array('', '0', '1', '2');
$batch_list     = array();

$sfl        = !empty($sfl) ? clean_xss_tags($sfl) : 'service_name';
$stx        = !empty($stx) ? clean_xss_tags($stx) : '';
$save_stx   = !empty($save_stx) ? clean_xss_tags($save_stx) : '';
$sst        = !empty($sst) ? clean_xss_tags($sst) : 'start_date';
$sod        = !empty($sod) ? clean_xss_tags($sod) : 'desc';
$status     = isset($status) ? preg_replace('/[^0-9]/', '',  $status) : '';

$search     = $g5Mysqli->whiteList($sfl, $search_list, 'Invalid field name');
$orderby    = $g5Mysqli->whiteList($sst, $orderby_list, 'Invalid field name');
$direction  = $g5Mysqli->whiteList($sod, $direction_list, 'Invalid ORDER BY direction');
$status     = $g5Mysqli->whiteList($status, $status_list, 'Invalid status');

$sql_common = '';
$sql_search = ' WHERE 1=1 ';
$sql_order  = "ORDER BY {$sst} {$sod}";
$bind_param = array();

$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

/* 검색조건 */
if ($stx != '' && $sfl != '') {
    $sql_search .= " AND {$search} LIKE ? ";
    array_push($bind_param, "%{$stx}%");
    if ($save_stx != $stx) {
        $page = 1;
    }
}

if ($status != '') {
    $sql_search .= " AND status = ? ";
    array_push($bind_param, $status);
}

$sql_common .= "FROM {$g5['batch_info_table']} bi
                LEFT JOIN {$g5['batch_service_table']} bs ON bi.service_id = bs.service_id
                LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id";
$sql_common .= $sql_search;

// 검색된 레코드 건수
$sql = "SELECT COUNT(*) as cnt {$sql_common}";
$result = $g5Mysqli->getOne($sql, $bind_param);
$total_count    = $result['cnt'] ? $result['cnt'] : 0;  // 전체 건수
$total_page     = ceil($total_count / $rows);           // 전체 페이지
$from_record    = ($page - 1) * $rows;                  // 시작 열

// 결제정보 목록 조회
$sql  = "SELECT
            bi.od_id, bi.start_date, bi.end_date, bi.status,
            mb.mb_id, mb.mb_name, mb.mb_email,
            bs.service_name,
            CONCAT(recurring_count, recurring_unit) AS recurring,
            (SELECT COUNT(*) FROM g5_batch_payment bp WHERE bp.od_id = bi.od_id AND res_cd = '0000') as payment_count,
            (SELECT price FROM g5_batch_service_price sd WHERE bs.service_id = sd.service_id AND (sd.apply_date <= NOW() OR sd.apply_date is null) ORDER BY apply_date DESC LIMIT 1) AS price
        {$sql_common}
        {$sql_order}
        limit ?, ?";
array_push($bind_param, $from_record, $rows);
$result = $g5Mysqli->execSQL($sql, $bind_param);
/* 결과처리 */
foreach ($result as $i => $row) {
    $batch_list[$i] = $row;
    $batch_list[$i]['bg_class'] = 'bg' . ($i % 2);
    // 결제주기
    $batch_list[$i]['recurring'] = strtr($row['recurring'], $unit_array);
    // 회원정보
    $batch_list[$i]['mb_side_view'] = get_sideview($row['mb_id'], get_text($row['mb_name']), $row['mb_email'], '');
    // 주문번호에 - 추가
    switch (strlen($row['od_id'])) {
        case 16:
            $batch_list[$i]['display_od_id'] = substr($row['od_id'], 0, 8) . '-' . substr($row['od_id'], 8);
            break;
        default:
            $batch_list[$i]['display_od_id'] = substr($row['od_id'], 0, 6) . '-' . substr($row['od_id'], 6);
            break;
    }
    // 기간
    $batch_list[$i]['display_date'] = $row['start_date'] . ' ~ ' . ($row['end_date'] != null && $row['end_date'] != '0000-00-00 00:00:00' ? $row['end_date'] : '');
    // 상태
    $batch_list[$i]['display_status'] = $row['status'] == '1' ? '진행 중' : '종료';
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

<form name="form_batch_list" class="local_sch03 local_sch">
    <input type="hidden" name="save_stx" value="<?php echo $stx; ?>">

    <div>
        <strong>결제상태</strong>
        <input type="radio" name="status" value="" id="status_all" <?php echo get_checked($status, ''); ?>>
        <label for="status_all">전체</label>
        <input type="radio" name="status" value="1" id="status_proceeding" <?php echo get_checked($status, '1'); ?>>
        <label for="status_proceeding">진행 중</label>
        <input type="radio" name="status" value="0" id="status_end" <?php echo get_checked($status, '0'); ?>>
        <label for="status_end">종료</label>
    </div>

    <div>
        <strong>검색</strong>
        <label for="sfl" class="sound_only">검색대상</label>
        <select name="sfl" id="sfl">
            <option value="service_name" <?php echo get_selected($sfl, 'service_name'); ?>>구독상품 명</option>
            <option value="mb.mb_id" <?php echo get_selected($sfl, 'mb.mb_id'); ?>>회원 ID</option>
            <option value="mb.mb_name" <?php echo get_selected($sfl, 'mb.mb_name'); ?>>회원 이름</option>
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
                    <th scope="col" rowspan="2" id="th_end_date">결제진행 상태</th>
                    <th scope="col" rowspan="2">보기</th>
                </tr>
                <tr>
                    <th scope="col" id="th_price">가격</th>
                    <th scope="col" id="th_recurring">주기</th>
                    <th scope="col" id="th_payment_count">결제횟수</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batch_list as $key => $batch) { ?>
                    <tr class="orderlist <?php echo $batch['bg_class']; ?>">
                        <td rowspan="2" class="td_chk2">
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
<?php
include_once G5_ADMIN_PATH . '/admin.tail.php';
