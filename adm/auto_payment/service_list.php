<?php
$sub_menu = '400920';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '구독상품 관리';
include_once G5_ADMIN_PATH . '/admin.head.php';

/** @todo add dbconfig.php */
$g5['batch_service_table']          = G5_TABLE_PREFIX . 'batch_service';
$g5['batch_service_price_table']    = G5_TABLE_PREFIX . 'batch_service_price';
$g5['batch_service_date_table']     = G5_TABLE_PREFIX . 'batch_service_date';
$unit_array = array("y" => "년", "m" => "개월", "w" => "주", "d" => "일");
$service_list = array();

$sfl        = !empty($sfl) ? clean_xss_tags($sfl) : "service_name";
$stx        = !empty($stx) ? clean_xss_tags($stx) : "";
$save_stx   = !empty($save_stx) ? clean_xss_tags($save_stx) : "";
$sst        = !empty($sst) ? clean_xss_tags($sst) : "service_id";
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

$sql_common .= "FROM {$g5['batch_service_table']} bs LEFT JOIN g5_board b ON bs.bo_table = b.bo_table ";
$sql_common .= $sql_search;
$sql_order  .= "ORDER BY {$sst} {$sod}";

// 검색된 레코드 건수
$sql = "SELECT COUNT(*) as cnt {$sql_common}";
$row = sql_fetch($sql);
$total_count    = $row['cnt'] ? $row['cnt'] : 0;// 전체 건수
$total_page     = ceil($total_count / $rows);   // 전체 페이지
$from_record    = ($page - 1) * $rows;          // 시작 열

// 구독상품 목록 조회
$sql  = "SELECT
            bs.*,
            b.bo_subject,
            (SELECT CONCAT(recurring_count, recurring_unit) FROM {$g5['batch_service_date_table']} sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS recurring,
	        (SELECT price FROM {$g5['batch_service_price_table']} sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS price
        {$sql_common}
        {$sql_order}
        limit {$from_record}, {$rows}";
$result = sql_query($sql);
for ($i = 0; $row = sql_fetch_array($result); $i++) {
    $service_list[$i] = $row;
    $service_list[$i]['bg_class'] = 'bg' . ($i % 2);
    // 결제주기
    $service_list[$i]['recurring'] = strtr($row['recurring'], $unit_array);
    // 구독 만료기간
    if ($row['service_expiration'] > 0) {
        $service_list[$i]['expiration'] = " ~ " . $row['service_expiration'] . $unit_array[$row['service_expiration_unit']];
    } else {
        $service_list[$i]['expiration'] = "없음";
    }
}

$qstr = $qstr . '&amp;page=' . $page . '&amp;save_stx=' . $stx;
?>

<div class="local_ov01 local_ov">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>" class="ov_listall">전체목록</a>
    <span class="btn_ov01"><span class="ov_txt">등록된 상품</span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
</div>

<form name="flist" class="local_sch01 local_sch">
    <input type="hidden" name="save_stx" value="<?php echo $stx; ?>">

    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="service_name" <?php echo get_selected($sfl, 'service_name'); ?>>구독상품 명</option>
        <option value="bo_subject" <?php echo get_selected($sfl, 'bo_subject'); ?>>게시판 명</option>
    </select>

    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input">
    <input type="submit" value="검색" class="btn_submit">
</form>

<form id="form_service_list" name="form_service_list" method="post" action="./service_list_update.php" onsubmit="return item_list_submit(this);" autocomplete="off">
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
                    <th scope="col" rowspan="2">
                        <label for="chkall" class="sound_only">상품 전체</label>
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col" rowspan="2"><?php echo subject_sort_link('bs.bo_table', 'sca=' . $sca); ?>게시판</a></th>
                    <th scope="col" rowspan="2"><?php echo subject_sort_link('service_name', 'sca=' . $sca); ?>구독상품명</a></th>
                    <th scope="col" rowspan="2">이미지</th>
                    <th scope="col" colspan="2"><?php echo subject_sort_link('price', 'sca=' . $sca); ?>가격</a></th>
                    <th scope="col" rowspan="2"><?php echo subject_sort_link('service_order', 'sca=' . $sca); ?>순서</a></th>
                    <th scope="col" rowspan="2"><?php echo subject_sort_link('service_use', 'sca=' . $sca, 1); ?>판매</a></th>
                    <th scope="col" rowspan="2">관리</th>
                </tr>
                <tr>
                    <th scope="col">결제주기</a></th>
                    <th scope="col">구독만료 기간</a></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($service_list as $key => $service) {
                ?>
                    <tr class="<?php echo $service['bg_class']; ?>">
                        <td rowspan="2" class="td_chk">
                            <label for="chk_<?php echo $key; ?>" class="sound_only"><?php echo get_text($service['service_name']); ?></label>
                            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $key; ?>">
                        </td>
                        <td rowspan="2">
                            <label class="sound_only"><?php echo get_text($service['bo_subject']); ?></label>
                            <?php echo $service['bo_subject']; ?>
                        </td>
                        <td rowspan="2">
                            <input type="text" name="service_name[<?php echo $key; ?>]" value="<?php echo $service['service_name']; ?>" class="tbl_input required" required="" size="30">
                        </td>
                        <td rowspan="2" class="td_img"><?php echo get_it_image($service['service_image'], 50, 50); ?></td>

                        <td colspan="2">
                            <?php echo number_format($service['price']); ?>원
                        </td>
                        <td rowspan="2" class="td_num">
                            <label for="order_<?php echo $key; ?>" class="sound_only">순서</label>
                            <input type="text" name="service_order[<?php echo $key; ?>]" value="<?php echo $service['service_order']; ?>" id="order_<?php echo $key; ?>" class="tbl_input" size="3">
                        </td>
                        <td rowspan="2">
                            <label for="use_<?php echo $key; ?>" class="sound_only">판매여부</label>
                            <input type="checkbox" name="service_use[<?php echo $key; ?>]" <?php echo ($service['service_use'] ? 'checked' : ''); ?> value="1" id="use_<?php echo $key; ?>">
                        </td>
                        <td rowspan="2" class="td_mng td_mng_s">
                            <a href="./service_form.php?w=u&amp;service_id=<?php echo $service['service_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03"><span class="sound_only"><?php echo htmlspecialchars2(cut_str($service['service_name'], 250, "")); ?> </span>수정</a>
                            <a href="./service_copy.php?it_id=<?php echo $service['service_id']; ?>" class="itemcopy btn btn_02" target="_blank"><span class="sound_only"><?php echo htmlspecialchars2(cut_str($service['service_name'], 250, "")); ?> </span>복사</a>
                        </td>
                    </tr>
                    <tr class="<?php echo $service['bg_class']; ?>">
                        <td>
                            <label class="sound_only">결제주기</label>
                            <?php echo $service['recurring'] ?>
                        </td>
                        <td>
                            <label class="sound_only">구독만료 기간</label>
                            <?php echo $service['expiration'] ?>
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
        <?php if ($is_admin == 'super') { ?>
            <input type="submit" name="act_button" value="선택 비활성화" onclick="document.pressed=this.value" class="btn btn_02">
        <?php } ?>
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
    function item_list_submit(f) {
        if (!is_checked("chk[]")) {
            alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
            return false;
        }

        return true;
    }

    $(function() {
        $(".itemcopy").click(function() {
            var href = $(this).attr("href");
            window.open(href, "copywin", "left=100, top=100, width=300, height=200, scrollbars=0");
            return false;
        });
    });
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
