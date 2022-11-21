<?php
$sub_menu = '400920';
include_once './_common.php';
include_once G5_EDITOR_LIB;
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

auth_check_menu($auth, $sub_menu, "w");

$html_title = '구독상품 ';
$g5Mysqli = new G5Mysqli();

// 구독상품 정보
$board_list     = array();

$service_id     = isset($_GET['service_id']) ? $_GET['service_id'] : 0;
$service        = array();
$price_count    = 0;

if ($w == '') {
    $html_title .= '입력';
} else if ($w == 'u') {
    $html_title .= '수정';

    $sql = "SELECT * FROM {$g5['batch_service_table']} WHERE service_id = ?";
    $service = $g5Mysqli->getOne($sql, array($service_id));
    if (!$service) {
        alert('상품정보가 존재하지 않습니다.');
    }
    // 구독상품 가격
    $sql_price = "SELECT * FROM {$g5['batch_service_price_table']} WHERE service_id = ?";
    $service_price = $g5Mysqli->execSQL($sql_price, array($service_id));
    $price_count = count($service_price);
} else {
    alert('올바르지 않은 접근입니다.');
}

// 게시판 리스트
$sql = "SELECT bo_table, bo_subject FROM {$g5['board_table']}";
$res_board = $g5Mysqli->execSQL($sql);
foreach ($res_board as $i => $row) {
    $board_list[$i] = $row;
    $board_list[$i]['selected'] = get_selected($row['bo_table'], $service['bo_table']);
}

// etc
$unit_array = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');
$qstr = $qstr . '&amp;sca=' . $sca . '&amp;page=' . $page;
$pg_anchor = '<ul class="anchor">
<li><a href="#anc_form_board">게시판 설정</a></li>
<li><a href="#anc_form_ini">기본정보</a></li>
<li><a href="#anc_form_cost">가격 및 결제주기</a></li>
</ul>';
$g5['title'] = $html_title;
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';
?>

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
                        <th scope="row"><label for="bo_table">게시판 선택</label></th>
                        <td>
                            <?php echo help("게시판을 선택하면 해당 구독서비스가 적용됩니다."); ?>
                            <select name="bo_table" id="bo_table">
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
    <section id="anc_form_ini">
        <h2 class="h2_frm">기본정보</h2>
        <?php echo $pg_anchor; ?>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>기본정보 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_3">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="service_name">구독상품명</label></th>
                        <td colspan="2">
                            <?php echo help("HTML 입력이 불가합니다."); ?>
                            <input type="text" name="service_name" value="<?php echo get_text(cut_str($service['service_name'], 250, "")); ?>" id="service_name" required class="frm_input required" size="95">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_summary">기본설명</label></th>
                        <td>
                            <?php echo help("구독상품명 하단에 상품에 대한 추가적인 설명이 필요한 경우에 입력합니다. HTML 입력도 가능합니다."); ?>
                            <input type="text" name="service_summary" value="<?php echo get_text(html_purifier($service['service_summary'])); ?>" id="it_basic" class="frm_input" size="95">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_order">출력순서</label></th>
                        <td>
                            <?php echo help("숫자가 작을 수록 상위에 출력됩니다. 음수 입력도 가능하며 입력 가능 범위는 -2147483648 부터 2147483647 까지입니다.\n<b>입력하지 않으면 자동으로 출력됩니다.</b>"); ?>
                            <input type="text" name="service_order" value="<?php echo $service['service_order']; ?>" id="service_order" class="frm_input" size="12">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_use">판매가능</label></th>
                        <td>
                            <?php echo help(""); ?>
                            <input type="checkbox" name="service_use" value="1" id="service_use" <?php echo ($service['service_use']) ? "checked" : ""; ?>> 예
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">상품설명</th>
                        <td colspan="2"> <?php echo editor_html('service_explan', get_text(html_purifier($service['service_explan']), 0)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">모바일 상품설명</th>
                        <td colspan="2"> <?php echo editor_html('service_mobile_explan', get_text(html_purifier($service['service_mobile_explan']), 0)); ?></td>
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
                    <col class="grid_3">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="price">가격 <button type="button" id="create_price_row" class="btn_frmline">가격 추가</button></label></th>
                        <td id="td_price">
                            <?php echo help("가격이 변경되는 일자를 선택할 수 있습니다."); ?>
                            <?php 
                                foreach ($service_price as $key => $price) {
                                    $row = $key + 1;
                            ?> 
                            <div id="td_price_<?php echo $row ?>">
                                <input type="hidden" name="price_id[<?php echo $row ?>]" value="<?php echo $price['price_id'] ?>">
                                <input type="text" name="price[<?php echo $row ?>]" value="<?php echo $price['price']; ?>" class="frm_input" size="12"> 원
                                / 
                                <input type="text" name="price_apply_date[<?php echo $row ?>]" id="price_apply_date_<?php echo $row ?>" value="<?php echo $price['apply_date']; ?>" id="apply_date" class="frm_input date_format" size="20"> 적용
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
                                <input type="text" name="recurring_count" value="<?php echo $service['recurring_count']; ?>" class="frm_input" size="12">
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
                            <input type="text" name="service_expiration" value="<?php echo $service['service_expiration']; ?>" id="service_expiration" class="frm_input" size="8" placeholder="0">
                            <select name="service_expiration_unit">
                            <?php foreach($unit_array as $key => $val) { ?>
                                <option value="<?php echo $key ?> " <?php echo get_selected($key, $service['service_expiration_unit']); ?>><?php echo $val ?></option>
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
        <a href="#" class="btn_02 btn">구독상품 보기</a>
        <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
    </div>
</form>


<script>
var f = document.form_service;
let price_row   = <?php echo $price_count ?>;

$(function() {
    if (price_row == 0) {
        create_price_row();
    }
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
        maxDate: "+1y"
    })
});

function create_price_row()
{
    price_row += 1;

    let html    = '';
    html += '<div id="td_price_' + price_row + '">';
    html += '<input type="text" name="price[' + price_row + ']" value="" class="frm_input" size="12"> 원 / ';
    html += '<input type="text" name="price_apply_date[' + price_row + ']" value="" id="price_apply_date_' + price_row + '" class="frm_input date_format" size="20"> 적용';
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
    if (price_row > 1) {
        $("#td_price_" + price_row).append(' <button type="button" name="remove_price_row" class="btn_frmline">삭제</button>');
    }
}

function set_datepicker()
{
    $('.date_format').datepicker();
}

function check_form_service(f)
{
    if (!f.bo_table.value) {
        alert("게시판을 선택하십시오.");
        f.bo_table.focus();
        return false;
    }

    <?php echo get_editor_js('service_explan'); ?>
    <?php echo get_editor_js('service_mobile_explan'); ?>

    return true;
}
</script>
<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');