<?php
$sub_menu = '800930';
include_once './_common.php';
include_once G5_EDITOR_LIB;

auth_check_menu($auth, $sub_menu, 'w');

/* 변수 선언 */
$html_title     = '구독상품 ';
$g5Mysqli       = G5Mysqli::getInstance();
$billing        = new Billing($billing_conf['bc_pg_code']);
$service_model  = new BillingServiceModel();
$price_model    = new BillingServicePriceModel();

$unit_period    = $billing->getUnitArray('period');
$service_id     = isset($_GET['service_id']) ? $_GET['service_id'] : 0;
$board_list     = array();
$service        = array(
    'service_id' => '',
    'service_table' => '',
    'name' => '',
    'order' => '',
    'is_use' => 1,
    'explan' => '',
    'mobile_explan' => '',
    'summary' => '',
    'recurring' => 1,
    'recurring_unit' => 'm',
    'expiration' => '0',
    'expiration_unit' => 'm',
    'is_event' => '',
    'is_event_checked' => '',
    'event_period' => 0,
    'event_unit' => 'm',
    'event_price' => 0
);
$service_price  = array();
$price_count = 0;

/* 데이터 출력 */
if ($w == '') {
    $html_title .= ' 입력';
} else if ($w == 'u') {
    $html_title .= ' 수정';
    // 구독상품 정보
    $service = $service_model->selectOneById($service_id);
    if (!$service) {
        alert('구독상품 정보가 존재하지 않습니다.');
    }
    // 결과처리
    $service['is_event_checked'] = ($service['is_event'] == '1' ? 'checked' : '');

    // 구독상품 가격정보
    $service_price  = $price_model->selectListByServiceId($service_id);
    $price_count    = count($service_price);
} else {
    alert('올바르지 않은 접근입니다.');
}

// 게시판 리스트
$result = $g5Mysqli->execSQL("SELECT bo_table, bo_subject FROM {$g5['board_table']} ORDER BY bo_order ASC");
foreach ($result as $i => $row) {
    $board_list[$i] = $row;
    $board_list[$i]['selected'] = get_selected($row['bo_table'], $service['service_table']);
}

// etc
$qstr = $qstr . '&amp;sca=' . $sca . '&amp;page=' . $page;
$pg_anchor = '<ul class="anchor">
<li><a href="#anc_form_board">게시판 설정</a></li>
<li><a href="#anc_form_info">기본정보</a></li>
<li><a href="#anc_form_cost">가격 및 결제주기</a></li>
</ul>';
$g5['title'] = $html_title;
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';

/* 가격변동 이력 modal popup */
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="' . G5_JS_URL . '/remodal/remodal.css">', 11);
add_stylesheet('<link rel="stylesheet" href="' . G5_JS_URL . '/remodal/remodal-default-theme.css">', 12);
add_javascript('<script src="' . G5_JS_URL . '/remodal/remodal.js"></script>', 10);

/* 가격변동 로그파일 불러오기 */
$log_content = '';
if (isset($service_id) && !empty($service_id)) {
    $log_path = G5_DATA_PATH . '/billing/log/service_price_change_log_' . $service_id . '.txt';
    $resource = fopen($log_path, 'r');
    if ($resource && filesize($log_path) > 0) {
        $log_content = fread($resource, filesize($log_path));
    }
}

?>
<form name="form_service" action="./service_update.php" method="post" enctype="multipart/form-data" autocomplete="off" onsubmit="return check_form_service(this)">
    <input type="hidden" name="w" value="<?php echo $w; ?>">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

    <section id="anc_form_board">
        <h2 class="h2_frm">게시판</h2>
        <?php echo $pg_anchor; ?>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>게시판 선택</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="service_table">게시판 선택</label></th>
                        <td>
                            <?php echo help("게시판을 선택하면 해당 구독서비스가 적용됩니다."); ?>
                            <select name="service_table" id="service_table">
                                <option value="">선택하세요</option>
                                <?php foreach ($board_list as $board) { ?>
                                    <option value="<?php echo $board['bo_table']; ?>" <?php echo $board['selected']; ?>><?php echo $board['bo_subject']; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <section id="anc_form_info">
        <h2 class="h2_frm">기본정보</h2>
        <?php echo $pg_anchor; ?>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>기본정보 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="name">구독상품명</label></th>
                        <td colspan="2">
                            <?php echo help("HTML 입력이 불가합니다."); ?>
                            <input type="text" name="name" value="<?php echo get_text(cut_str($service['name'], 250, "")); ?>" id="name" required class="frm_input required" size="95">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="summary">기본설명</label></th>
                        <td>
                            <?php echo help("구독상품명 하단에 상품에 대한 추가적인 설명이 필요한 경우에 입력합니다. HTML 입력도 가능합니다."); ?>
                            <input type="text" name="summary" value="<?php echo get_text(html_purifier($service['summary'])); ?>" id="it_basic" class="frm_input" size="95">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="order">출력순서</label></th>
                        <td>
                            <?php echo help("숫자가 작을 수록 상위에 출력됩니다. 음수 입력도 가능하며 입력 가능 범위는 -2147483648 부터 2147483647 까지입니다.\n<b>입력하지 않으면 자동으로 출력됩니다.</b>"); ?>
                            <input type="text" name="order" value="<?php echo $service['order']; ?>" id="order" class="frm_input" size="12">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">상품설명</th>
                        <td colspan="2"> <?php echo editor_html('explan', get_text(html_purifier(str_replace('\"', '', $service['explan'])), 0)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">모바일 상품설명</th>
                        <td colspan="2"> <?php echo editor_html('mobile_explan', get_text(html_purifier(str_replace('\"', '', $service['mobile_explan'])), 0)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="anc_form_cost">
        <h2 class="h2_frm">가격 및 결제주기</h2>
        <?php echo $pg_anchor; ?>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>가격 및 결제주기 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="is_use">판매가능</label></th>
                        <td>
                            <?php echo help(""); ?>
                            <input type="checkbox" name="is_use" value="1" id="is_use" <?php echo ($service['is_use']) ? "checked" : ""; ?>> 예
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="price">
                                가격 설정
                                <?php if (!empty($service_id)) { ?>
                                    <div style="margin-top:10px;">
                                        <button type="button" data-remodal-target='modal_price_log' class="btn_frmline">변동이력 확인</button>
                                    </div>
                                <?php } ?>
                            </label>
                        </th>
                        <td id="td_price">
                            <?php echo help("가격이 변경되는 일자를 선택할 수 있습니다. 설정된 가격은 해당 날짜에 맞춰 자동으로 반영됩니다. 
                            <b>※ 가격 변경기간이 중복되지 않도록 주의해주시기 바랍니다.</b>"); ?>
                            <div><button type="button" id="create_price_row" class="btn_frmline">가격 추가</button></div>
                            <?php
                            foreach ($service_price as $key => $price) {
                                $row = (int)$key + 1;
                            ?>
                                <div id="td_price_<?php echo $row ?>" style="margin-top:4px;">
                                    <input type="hidden" name="id[<?php echo $row ?>]" value="<?php echo $price['id'] ?>">
                                    <input type="text" name="price[<?php echo $row ?>]" value="<?php echo $price['price']; ?>" class="frm_input" size="12">&nbsp;원&nbsp;
                                    <input type="text" name="application_date[<?php echo $row ?>]" id="application_date_<?php echo $row ?>" value="<?php echo $price['application_date']; ?>" class="frm_input date_format" size="20" placeholder="시작일">&nbsp;부터 적용&nbsp;
                                    <input type="text" name="memo[<?php echo $row ?>]" id="memo<?php echo $row ?>" value="<?php echo $price['memo']; ?>" class="frm_input" size="40" placeholder="적요">
                                    <button type="button" name="remove_price_row" class="btn_frmline">삭제</button>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">결제주기</label></th>
                        <td>
                            <?php echo help("결제가 진행되는 주기를 설정할 수 있습니다."); ?>
                            <div>
                                <input type="text" name="recurring" value="<?php echo $service['recurring']; ?>" class="frm_input required" size="12">
                                <select name="recurring_unit">
                                    <?php foreach ($unit_period as $key => $val) { ?>
                                        <option value="<?php echo $key ?>" <?php echo get_selected($key, $service['recurring_unit']); ?>><?php echo $val ?></option>
                                    <?php } ?>
                                </select>
                                주기로 결제진행
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">구독만료 기간 설정</label></th>
                        <td>
                            <?php echo help("구독서비스가 종료되는 만기 기간을 설정합니다.\n만료기간을 설정하지 않는다면 빈 값으로 입력해주시기 바랍니다."); ?>
                            결제일로부터
                            <input type="text" name="expiration" value="<?php echo $service['expiration']; ?>" id="expiration" class="frm_input" size="8" placeholder="0">
                            <select name="expiration_unit">
                                <?php foreach ($unit_period as $key => $val) { ?>
                                    <option value="<?php echo $key ?> " <?php echo get_selected($key, $service['expiration_unit']); ?>><?php echo $val ?></option>
                                <?php } ?>
                            </select>
                            이후 종료
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">첫 구독 이벤트 설정</label></th>
                        <td>
                            <?php echo help("첫 구독 신청부터 적용되는 이벤트가격 & 적용기간을 설정할 수 있습니다."); ?>
                            <div>
                                <input type="checkbox" name="is_event" value="1" id="is_event" <?php echo $service['is_event_checked'] ?>>
                                <label for="is_event">사용</label>
                                <div id="event_area">
                                    처음 결제부터
                                    <input type="text" name="event_period" id="event_period" value="<?php echo $service['event_period']; ?>" class="frm_input" size="8" placeholder="0">
                                    <select name="event_unit">
                                        <?php foreach ($unit_period as $key => $val) { ?>
                                            <option value="<?php echo $key ?>" <?php echo get_selected($key, $service['event_unit']); ?>><?php echo $val ?></option>
                                        <?php } ?>
                                    </select>
                                    동안
                                    <input type="text" name="event_price" value="<?php echo $service['event_price']; ?>" class="frm_input" size="12"> 원으로 결제
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="btn_fixed_top">
        <a href="./service_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
        <a href="<?php echo G5_URL . '/bbs/subscription/view.php?service_id=' . $service['service_id'] ?>" target="_blank" class="btn_02 btn">구독상품 보기</a>
        <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
    </div>
</form>

<div class="server_rewrite_info">
    <div class="is_rewrite remodal" data-remodal-id="modal_price_log" role="dialog" aria-labelledby="modalPrice" aria-describedby="modal1Desc">

        <button type="button" class="connect-close" data-remodal-action="close">
            <i class="fa fa-close"></i>
            <span class="txt">닫기</span>
        </button>

        <h4 class="copy_title">해당 구독상품의 가격변동 이력입니다.</h4>
        <textarea readonly="readonly" rows="10"><?php echo $log_content ?></textarea>
    </div>
</div>
<script>
    let price_row = <?php echo $price_count ?>;

    $(function() {
        set_datepicker();
        toggle_event_area();
        if (price_row == 0) {
            create_price_row();
        }

        // 가격추가 버튼
        $(document).on("click", "#create_price_row", function() {
            create_price_row();
            add_remove_price_btn();
            set_datepicker();
        });
        // 가격삭제 버튼
        $(document).on("click", "button[name='remove_price_row']", function() {
            remote_price_row(this);
            // add_remove_price_btn();
        });
        // 첫결제 이벤트 사용 체크
        $(document).on('click', '#is_event', function() {
            toggle_event_area();
        })


        $.datepicker.setDefaults({
            changeMonth: true,
            changeYear: true,
            dateFormat: "yy-mm-dd 00:00:00",
            showButtonPanel: true,
            yearRange: "c-99:c+99",
            maxDate: "+10y"
        })
    });

    function create_price_row() {
        price_row += 1;

        let html = '';
        html += '<div id="td_price_' + price_row + '" style="margin-top:4px;">';
        html += '<input type="text" name="price[' + price_row + ']" value="" class="frm_input" size="12">&nbsp;원&nbsp;&nbsp;';
        html += '<input type="text" name="application_date[' + price_row + ']" value="" id="application_date_' + price_row + '" class="frm_input date_format" size="20" placeholder="시작일">&nbsp;부터 적용&nbsp;&nbsp;';
        html += '<input type="text" name="memo[' + price_row + ']" id="memo' + price_row + '" value="" class="frm_input" size="40" placeholder="적요">';
        html += '</div>';
        $("#td_price").append(html);
    }

    function remote_price_row(obj) {
        price_row -= 1;
        $(obj).parent().remove();
    }

    function add_remove_price_btn() {
        // $("button[name='remove_price_row']").remove();
        if (price_row > 0) {
            $("#td_price_" + price_row).append(' <button type="button" name="remove_price_row" class="btn_frmline">삭제</button>');
        }
    }

    function set_datepicker() {
        $('.date_format').datepicker();
    }

    function check_form_service(f) {
        if (!f.service_table.value) {
            alert("게시판을 선택해주세요.");
            f.service_table.focus();
            return false;
        }

        if (f.recurring.value <= 0) {
            alert("결제주기는 0보다 큰 숫자로 입력해주세요.");
            f.recurring.focus();
            return false;
        }

        for (let i = 1; i <= price_row; i++) {
            if ($("#application_date_" + i).val() === '') {
                alert("변경 가격의 시작날짜를 입력해주세요.");
                $("#application_date_" + i).focus();
                return false;
            }
        }

        <?php echo get_editor_js('explan'); ?>
        <?php echo get_editor_js('mobile_explan'); ?>

        return true;
    }

    function toggle_event_area() {
        if ($('#is_event').is(':checked')) {
            $('#event_area').show();
        } else {
            $('#event_area').hide();
        }
    }
</script>
<?php
include_once G5_ADMIN_PATH . '/admin.tail.php';
