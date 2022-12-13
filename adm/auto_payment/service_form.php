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

$unit_array     = $billing->getUnitArray();
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
    'base_price' => '',
    'recurring_unit' => 'm',
    'expiration' => '0',
    'expiration_unit' => 'm'
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

$log_content = '';
if (isset($service_id) && !empty($service_id)) {
    $log_path = G5_DATA_PATH . '/billing/log/service_price_change_log_' . $service_id . '.txt';
    $resource = fopen($log_path, 'r');
    if ($resource && filesize($log_path) > 0) {
        $log_content = fread($resource, filesize($log_path));
    }
}

?>
<style>
.help_modal_log {
    font-weight: bold;
    text-decoration-line: underline;
    cursor: pointer;
}
</style>
<form name="form_service" action="./service_update.php" method="post" enctype="multipart/form-data"
    autocomplete="off" onsubmit="return check_form_service(this)">
    <input type="hidden" name="w" value="<?php echo $w; ?>">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod"  value="<?php echo $sod; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx"  value="<?php echo $stx; ?>">
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
                                <?php foreach($board_list as $board) { ?>
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
                        <th scope="row"><label for="is_use">판매가능</label></th>
                        <td>
                            <?php echo help(""); ?>
                            <input type="checkbox" name="is_use" value="1" id="is_use" <?php echo ($service['is_use']) ? "checked" : ""; ?>> 예
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
                        <th scope="row"><label for="base_price">기본 가격</label></th>
                        <td>
                            <?php echo help("기본 가격을 설정합니다."); ?>
                            <div>
                                <input type="number" name="base_price" value="<?php echo $service['base_price']; ?>" class="frm_input" size="12"> 원
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="price">변경 가격 
                                <button type="button" id="create_price_row" class="btn_frmline">가격 추가</button>
                            </label>
                        </th>
                        <td id="td_price">
                            <?php echo help("가격이 변경되는 일자를 선택할 수 있습니다. 설정된 가격은 해당 날짜에 맞춰 자동으로 반영됩니다.
                            <span class='help_modal_log' data-remodal-target='modal_price_log'>가격변동 이력을 확인</span>할 수 있습니다."); ?>
                        <?php 
                            foreach ($service_price as $key => $price) {
                                $row = (int)$key + 1;
                        ?> 
                            <div id="td_price_<?php echo $row ?>">
                                <input type="hidden" name="id[<?php echo $row ?>]" value="<?php echo $price['id'] ?>">
                                <input type="text" name="price[<?php echo $row ?>]" value="<?php echo $price['price']; ?>" class="frm_input" size="12"> 원
                                / 
                                <input type="text" name="application_date[<?php echo $row ?>]" id="application_date_<?php echo $row ?>" value="<?php echo $price['application_date']; ?>" class="frm_input date_format" size="20"> ~
                                <input type="text" name="application_end_date[<?php echo $row ?>]" id="application_end_date_<?php echo $row ?>" value="<?php echo $price['application_end_date']; ?>" class="frm_input date_format" size="20">까지 적용
                                <?php if ($price_count == $row) { ?>
                                    <button type="button" name="remove_price_row" class="btn_frmline">삭제</button>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">결제주기</label></th>
                        <td>
                            <?php echo help("결제가 진행되는 주기를 설정할 수 있습니다."); ?>
                            <div>
                                <input type="text" name="recurring" value="<?php echo $service['recurring']; ?>" class="frm_input" size="12">
                                <select name="recurring_unit">
                                <?php foreach($unit_array as $key => $val) { ?>
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
                            <?php echo help("결제일로부터 구독서비스가 종료되는 만기 기간을 설정합니다."); ?>
                            결제일로부터
                            <input type="text" name="expiration" value="<?php echo $service['expiration']; ?>" id="expiration" class="frm_input" size="8" placeholder="0">
                            <select name="expiration_unit">
                            <?php foreach($unit_array as $key => $val) { ?>
                                <option value="<?php echo $key ?> " <?php echo get_selected($key, $service['expiration_unit']); ?>><?php echo $val ?></option>
                            <?php } ?>
                            </select>
                            이후 종료
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

        <h4 class="copy_title">해당 구독상품의 가격변동 이력입니다.
            <!-- <br><span class="info-warning"></span>
            <br><span class="info-success"></span> -->
        </h4>
        <textarea readonly="readonly" rows="10"><?php echo $log_content ?></textarea>
    </div>
</div>
<script>
let price_row   = <?php echo $price_count ?>;

$(function() {
    set_datepicker();

    // 가격추가 버튼
    $(document).on("click", "#create_price_row", function(){
        create_price_row();
        add_remove_price_btn();
        set_datepicker();
    });
    // 가격삭제 버튼
    $(document).on("click", "button[name='remove_price_row']", function(){
        remote_price_row(this);
        add_remove_price_btn();
    });

    $.datepicker.setDefaults({
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd 00:00:00",
        showButtonPanel: true,
        yearRange: "c-99:c+99",
        maxDate: "+10y"
    })
});

function create_price_row()
{
    price_row += 1;

    let html    = '';
    html += '<div id="td_price_' + price_row + '">';
    html += '<input type="text" name="price[' + price_row + ']" value="" class="frm_input" size="12"> 원 / ';
    html += '<input type="text" name="application_date[' + price_row + ']" value="" id="application_date_' + price_row + '" class="frm_input date_format" size="20"> ~ ';
    html += '<input type="text" name="application_end_date[' + price_row + ']" value="" id="application_end_date_' + price_row + '" class="frm_input date_format" size="20">까지 적용';
    html += '</div>';
    $("#td_price").append(html);
}
function remote_price_row(obj)
{
    price_row   -= 1;
    $(obj).parent().remove();
}
function add_remove_price_btn()
{
    $("button[name='remove_price_row']").remove();
    if (price_row > 0) {
        $("#td_price_" + price_row).append(' <button type="button" name="remove_price_row" class="btn_frmline">삭제</button>');
    }
}

function set_datepicker()
{
    $('.date_format').datepicker();
}

function check_form_service(f)
{
    if (!f.service_table.value) {
        alert("게시판을 선택하십시오.");
        f.service_table.focus();
        return false;
    }

    for (let i = 1; i <= price_row; i++) {
        if ($("#application_date_" + i).val() === '') {
            alert("변경 가격의 시작날짜를 입력해주세요.");
            $("#application_date_" + i).focus();
            return false;
        }
        // 마지막 행은 제외하고 검사
        if (($("#application_end_date_" + i).val() === '' || $("#application_end_date_" + i).val() === '0000-00-00 00:00:00')
            && i != price_row) {
            alert("변경 가격의 종료날짜를 입력해주세요.");
            $("#application_end_date_" + i).focus();
            return false;
        }

    }

    <?php echo get_editor_js('explan'); ?>
    <?php echo get_editor_js('mobile_explan'); ?>

    return true;
}
</script>
<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');