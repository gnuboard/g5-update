<?php
$sub_menu = '800930';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '구독상품 관리';
include_once G5_ADMIN_PATH . '/admin.head.php';

/* 변수 선언 */
$billing        = new Billing($billing_conf['bc_pg_code']);
$service_model  = new BillingServiceModel();
$price_model    = new BillingServicePriceModel();

$service_list   = array();
$sfl        = !empty($sfl) ? clean_xss_tags($sfl) : 'name';
$stx        = !empty($stx) ? clean_xss_tags($stx) : '';
$sst        = !empty($sst) ? clean_xss_tags($sst) : 'service_id';
$sod        = !empty($sod) ? clean_xss_tags($sod) : 'desc';

$rows       = $config['cf_page_rows'];
$page       = ($page > 1) ? $page : 1;

$request_data = array(
    "sfl"   => $sfl,
    "stx"   => $stx,
    "sst"   => $sst,
    "sod"   => $sod
);

/* 데이터 출력 */
// 전체 건수
$total_count    = $service_model->selectTotalCount($request_data);
$total_page     = ceil($total_count / $rows);   // 전체 페이지
$request_data['offset'] = ($page - 1) * $rows;  // 시작 열
$request_data['rows']   = $rows;

// 목록 조회
$service_list = $service_model->selectList($request_data);

foreach ($service_list as $i => $service) {
    $service_list[$i]['bg_class']           = 'bg' . ($i % 2);
    $service_list[$i]['display_price']      = is_null($cuPrice = $price_model->selectCurrentPrice($service['service_id'])) ? '오픈 전' : number_format($cuPrice) . '원';
    $service_list[$i]['display_recurring']  = ($dsRec = $billing->displayRecurring($service)) ? $dsRec : '없음';
    $service_list[$i]['display_expiration'] = ($dsExp = $billing->displayExpiration($service)) ? '결제일로부터 ' . $dsExp . ' 이후' : '없음';
    if ($service['is_event'] == "1") {
        $service_list[$i]['display_event'] = number_format($service['event_price']) . '원<br>';
        $service_list[$i]['display_event'] .= "(첫 결제부터 " . $service['event_period'] . $billing->convertDateUnitToText($service['event_unit'], 'period') . " 동안)";
    } else {
        $service_list[$i]['display_event'] = '없음';
    }
}

$qstr = $qstr . '&amp;page=' . $page;
?>

<div class="local_ov01 local_ov">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>" class="ov_listall">전체목록</a>
    <span class="btn_ov01"><span class="ov_txt">등록된 상품</span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
</div>

<form name="flist" class="local_sch01 local_sch">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="name" <?php echo get_selected($sfl, 'name'); ?>>구독상품 명</option>
        <option value="bo_subject" <?php echo get_selected($sfl, 'bo_subject'); ?>>게시판 명</option>
    </select>

    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input">
    <input type="submit" value="검색" class="btn_submit">
</form>

<form id="form_service_list" name="form_service_list" method="post" action="./service_list_update.php" onsubmit="return service_list_submit(this);" autocomplete="off">
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
                    <th scope="col" rowspan="2" style="width:5%;">
                        <label for="chkall" class="sound_only">상품 전체</label>
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col" rowspan="2" style="width:10%;"><?php echo subject_sort_link('service_table', 'sca=' . $sca); ?>게시판</a></th>
                    <th scope="col" rowspan="2" style="width:30%;"><?php echo subject_sort_link('name', 'sca=' . $sca); ?>구독상품명</a></th>
                    <!-- <th scope="col" rowspan="2" style="width:10%;">이미지</th> -->
                    <th scope="col" colspan="2" style="width:20%;">가격</th>
                    <th scope="col" rowspan="2" style="width:10%;">이벤트</th>
                    <th scope="col" rowspan="2" style="width:5%;"><?php echo subject_sort_link('order', 'sca=' . $sca); ?>순서</a></th>
                    <th scope="col" rowspan="2" style="width:5%;"><?php echo subject_sort_link('is_use', 'sca=' . $sca, 1); ?>판매</a></th>
                    <th scope="col" rowspan="2" style="width:5%;">관리</th>
                </tr>
                <tr>
                    <th scope="col" style="width:10%;">결제주기</a></th>
                    <th scope="col" style="width:10%;">구독만료 기간</a></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($service_list as $key => $service) { ?>
                    <tr class="<?php echo $service['bg_class']; ?>">
                        <td rowspan="2">
                            <label for="chk_<?php echo $key; ?>" class="sound_only"><?php echo get_text($service['name']); ?></label>
                            <input type="checkbox" name="chk[]" value="<?php echo $key ?>" id="chk_<?php echo $key; ?>">
                            <input type="hidden" name="service_id[<?php echo $key; ?>]" value="<?php echo $service['service_id'] ?>">
                        </td>
                        <td rowspan="2">
                            <label class="sound_only"><?php echo get_text($service['bo_subject']); ?></label>
                            <?php echo $service['bo_subject']; ?>
                        </td>
                        <td rowspan="2">
                            <input type="text" name="name[<?php echo $key; ?>]" value="<?php echo $service['name']; ?>" class="tbl_input required" required="" size="30">
                        </td>
                        <!-- <td rowspan="2">
                            <?php echo get_it_image($service['image_path'], 50, 50); ?>
                        </td> -->
                        <td colspan="2">
                            <?php echo $service['display_price']; ?>
                        </td>
                        <td rowspan="2"><?php echo $service['display_event'] ?></td>
                        <td rowspan="2">
                            <label for="order_<?php echo $key; ?>" class="sound_only">순서</label>
                            <input type="text" name="order[<?php echo $key; ?>]" value="<?php echo $service['order']; ?>" id="order_<?php echo $key; ?>" class="tbl_input" size="3">
                        </td>
                        <td rowspan="2">
                            <label for="use_<?php echo $key; ?>" class="sound_only">판매여부</label>
                            <input type="checkbox" name="is_use[<?php echo $key; ?>]" <?php echo ($service['is_use'] ? 'checked' : ''); ?> value="1" id="use_<?php echo $key; ?>">
                        </td>
                        <td rowspan="2">
                            <a href="./service_form.php?w=u&amp;service_id=<?php echo $service['service_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">
                                <span class="sound_only"><?php echo htmlspecialchars2(cut_str($service['name'], 250, "")); ?> </span>
                                수정
                            </a>
                        </td>
                    </tr>
                    <tr class="<?php echo $service['bg_class']; ?>">
                        <td>
                            <label class="sound_only">결제주기</label>
                            <?php echo $service['display_recurring'] ?>
                        </td>
                        <td>
                            <label class="sound_only">구독만료 기간</label>
                            <?php echo $service['display_expiration'] ?>
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
        <a href="./service_form.php" class="btn btn_01">상품등록</a>
        <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value" class="btn btn_02">
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
    function service_list_submit(f) {
        if (!is_checked("chk[]")) {
            alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
            return false;
        }
        return true;
    }
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
