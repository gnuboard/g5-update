<?php
$sub_menu = '400930';
include_once './_common.php';
require_once G5_LIB_PATH . '/billing/kcp/config.php';
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '구독결제 내역';
include_once G5_ADMIN_PATH . '/admin.head.php';

/* 변수 선언 */
$information_model  = new BillingInformationModel();
$price_model        = new BillingServicePriceModel();
$history_model      = new BillingHistoryModel();

$unit_array     = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');
$search_list    = array('service_name', 'mb.mb_id', 'mb.mb_name');
$orderby_list   = array('service_id', 'service_name', 'price', 'service_order', 'service_use', 'start_date');
$direction_list = array('desc', 'asc');
$status_list    = array('', '0', '1', '2');

$sfl        = !empty($sfl) ? clean_xss_tags($sfl) : 'service_name';
$stx        = !empty($stx) ? clean_xss_tags($stx) : '';
$save_stx   = !empty($save_stx) ? clean_xss_tags($save_stx) : '';
$sst        = !empty($sst) ? clean_xss_tags($sst) : 'start_date';
$sod        = !empty($sod) ? clean_xss_tags($sod) : 'desc';
$status     = isset($status) ? preg_replace('/[^0-9]/', '',  $status) : '';

$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

$request_data = array(
    "sfl"   => $sfl,
    "stx"   => $stx,
    "sst"   => $sst,
    "sod"   => $sod,
    "status"=> $status
);

$total_count    = $information_model->selectTotalCount($request_data);   // 전체 건수
$total_page     = ceil($total_count / $rows);           // 전체 페이지
$offset         = ($page - 1) * $rows;                  // 시작 열
$request_data['offset'] = $offset;
$request_data['rows']   = $rows;

$billing_list = $information_model->selectList($request_data);

/* 결과처리 */
foreach ($billing_list as $i => $row) {
    $billing_list[$i] = $row;
    $billing_list[$i]['bg_class'] = 'bg' . ($i % 2);
    // 가격
    $billing_list[$i]['price'] = $price_model->selectCurrentPrice($row['service_id']);
    // 결제주기
    $billing_list[$i]['display_recurring'] = $row['recurring'] . strtr($row['recurring_unit'], $unit_array);
    // 회원정보
    $billing_list[$i]['mb_side_view'] = get_sideview($row['mb_id'], get_text($row['mb_name']), $row['mb_email'], '');
    // 주문번호에 - 추가
    switch (strlen($row['od_id'])) {
        case 16:
            $billing_list[$i]['display_od_id'] = substr($row['od_id'], 0, 8) . '-' . substr($row['od_id'], 8);
            break;
        default:
            $billing_list[$i]['display_od_id'] = substr($row['od_id'], 0, 6) . '-' . substr($row['od_id'], 6);
            break;
    }
    // 기간
    $billing_list[$i]['display_date'] = $row['start_date'] . ' ~ ' . ($row['end_date'] != null && $row['end_date'] != '0000-00-00 00:00:00' ? $row['end_date'] : '');
    // 상태
    $billing_list[$i]['display_status'] = $row['status'] == '1' ? '진행 중' : '종료';
    // 결제회차
    $billing_list[$i]['payment_count'] = $history_model->selectPaymentCount($row['od_id']);
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
            <option value="bs.name" <?php echo get_selected($sfl, 'bs.name'); ?>>구독서비스 이름</option>
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
                <?php foreach ($billing_list as $key => $billing) { ?>
                    <tr class="orderlist <?php echo $billing['bg_class']; ?>">
                        <td rowspan="2" class="td_chk2">
                            <input type="hidden" name="od_id[<?php echo $key ?>]" value="<?php echo $billing['od_id'] ?>" id="od_id_<?php echo $key ?>">
                            <label for="chk_<?php echo $i; ?>" class="sound_only">주문번호 <?php echo $billing['od_id']; ?></label>
                            <input type="checkbox" name="chk[]" value="<?php echo $key ?>" id="chk_<?php echo $key ?>">
                        </td>
                        <td headers="th_od_id" rowspan="2">
                            <a href="<?php echo G5_SHOP_URL; ?>/orderinquiryview.php?od_id=<?php echo $billing['od_id']; ?>" class="orderitem">
                                <?php echo $billing['display_od_id']; ?>
                            </a>
                        </td>
                        <td headers="th_service_name" colspan="3"><?php echo $billing['name']; ?></td>
                        <td headers="th_member" rowspan="2"><?php echo $billing['mb_side_view']; ?></td>
                        <td headers="th_date" rowspan="2"><?php echo $billing['display_date']; ?></td>
                        <td headers="th_status" rowspan="2"><?php echo $billing['display_status']; ?></td>
                        <td rowspan="2" class="td_mng td_mng_s">
                            <a href="./batch_form.php?od_id=<?php echo $billing['od_id']; ?>&amp;<?php echo $qstr; ?>" class="mng_mod btn btn_02">
                                <span class="sound_only"><?php echo $billing['od_id']; ?></span>
                                보기
                            </a>
                        </td>
                    </tr>
                    <tr class="<?php echo $billing['bg_class']; ?>">
                        <td><?php echo number_format($billing['price']) ?>원</td>
                        <td><?php echo $billing['display_recurring'] ?></td>
                        <td><?php echo number_format((float)$billing['payment_count']); ?>회</td>
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
