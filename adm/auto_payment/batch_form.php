<?php
$sub_menu = '400930';
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

auth_check_menu($auth, $sub_menu, "w");

$g5['title'] = "구독결제 수정";
$g5Mysqli = new G5Mysqli();

$unit_array     = array("y" => "년", "m" => "개월", "w" => "주", "d" => "일");
$od_id          = isset($_REQUEST['od_id']) ? safe_replace_regex($_REQUEST['od_id'], 'od_id') : '';
$service_id     = null;
$offset         = 4;
$repeat_time    = 8;

/* 구독정보 */
$batch_info     = array();

// 구독정보 정보 조회
$sql = "SELECT bi.*, mb_name, mb_email FROM {$g5['batch_info_table']} bi LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id WHERE od_id = ?";
$batch_info = $g5Mysqli->getOne($sql, array($od_id));
// 결과처리
$batch_info['mb_side_view']         = get_sideview($batch_info['mb_id'], get_text($batch_info['mb_name']), $batch_info['mb_email'], '');
$batch_info['display_end_date']     = $batch_info['end_date'] != "0000-00-00 00:00:00" ? $batch_info['end_date'] : "없음";
$batch_info['display_status']       = $batch_info['status'] == "1" ? "진행 중" : "종료";
$batch_info['display_batch_key']    = substr_replace($batch_info['batch_key'], str_repeat('*', $repeat_time), $offset, $repeat_time); // 배치키 * 표시
switch(strlen($batch_info['od_id'])) {
    case 16:
        $batch_info['display_od_id'] = substr($batch_info['od_id'] ,0 ,8) . '-' . substr($batch_info['od_id'], 8);
        break;
    default:
        $batch_info['display_od_id'] = substr($batch_info['od_id'] , 0, 6) . '-' . substr($batch_info['od_id'], 6);
        break;
}

/* 구독상품 */
// 구독상품 정보 조회
$service_id = $batch_info['service_id'];
$sql = "SELECT
            bs.service_id, bs.bo_table, bs.service_name, bs.service_image, bs.service_url, bs.service_hook, bs.service_order, bs.service_use,
            IF(service_expiration != 0, CONCAT(bs.service_expiration, bs.service_expiration_unit), '') AS expiration,
            b.bo_subject,
            (SELECT CONCAT(recurring_count, recurring_unit) FROM {$g5['batch_service_date_table']} sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS recurring,
	        (SELECT price FROM {$g5['batch_service_price_table']} sd WHERE bs.service_id = sd.service_id AND sd.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS price
        FROM {$g5['batch_service_table']} bs LEFT JOIN g5_board b ON bs.bo_table = b.bo_table
        WHERE bs.service_id = ?";
$service = $g5Mysqli->getOne($sql, array($service_id));
// 결과처리
$service['display_expiration']  = ($service['expiration'] != '') ? '결제일로부터 ' . strtr($service['expiration'], $unit_array) : '없음';
$service['display_recurring']   = strtr($service['recurring'], $unit_array);

// 변경예정 가격 최근 1건 조회
$price_schedule = array();
$sql_price_schedule = "SELECT
                            price, apply_date
                        FROM {$g5['batch_service_price_table']}
                        WHERE service_id = ?
                            AND apply_date > now()
                        ORDER BY apply_date ASC 
                        LIMIT 1";
$price_schedule = $g5Mysqli->getOne($sql_price_schedule, array($service_id));
if (isset($price_schedule)) {
    $price_schedule['display_price'] = number_format($price_schedule['price']);
    $price_schedule['display_apply_date'] = date('Y-m-d', strtotime($price_schedule['apply_date'])) . " 반영";
}

// 변경예정 결제주기 최근 1건 조회
$date_schedule  = array();
$sql_date_schedule = "SELECT 
                            CONCAT(recurring_count, recurring_unit) AS recurring,
                            apply_date
                        FROM {$g5['batch_service_date_table']} 
                        WHERE service_id = ?
                            AND apply_date > now() 
                        ORDER BY apply_date ASC
                        LIMIT 1";
$date_schedule = $g5Mysqli->getOne($sql_date_schedule, array($service_id));
if (isset($date_schedule)) {
    $date_schedule['recurring'] = strtr($date_schedule['recurring'], $unit_array);
    $date_schedule['display_apply_date'] = date('Y-m-d', strtotime($date_schedule['apply_date']))  . " 반영";
}

// 결제내역 조회
$total_amount = 0;
$payment_list = array();
$payment_success = array();
$sql = "SELECT * FROM {$g5['batch_payment_table']} WHERE od_id = ? ORDER BY payment_count DESC, date DESC";
$result = $g5Mysqli->execSQL($sql, array($od_id));
foreach ($result as $i => $row) {
    $count = $row['payment_count'];
    $payment_list[$i] = $row;
    $payment_list[$i]['display_payment_count']  = $row['payment_count'] . "회차";
    $payment_list[$i]['display_amount']         = number_format($row['amount']) . "원";
    $payment_list[$i]['display_res_cd']         = ($row['res_cd'] == "0000" ? "성공" : "실패");
    $payment_list[$i]['display_res_cd_color']   = ($row['res_cd'] == "0000" ? "#53C14B" : "#FF0000");
    $payment_list[$i]['display_batch_key']      = substr_replace($row['batch_key'], str_repeat('*', $repeat_time), $offset, $repeat_time); // 배치키 * 표시
    $payment_list[$i]['is_btn_refund']          = false;

    if (!isset($payment_success[$count])) {
        $payment_success[$count] = false;
    }
    
    if ($row['res_cd'] == "0000") {
        $payment_list[$i]['is_btn_refund'] = true;
        $payment_success[$count] = true;
        $total_amount += $row['amount'];
    }
}


// etc
$pg_anchor = '<ul class="anchor">
<li><a href="#anc_batch_info">구독결제 정보</a></li>
<li><a href="#anc_batch_payment">결제내역</a></li>
</ul>';
include_once G5_ADMIN_PATH . '/admin.head.php';
?>
<section class="">
    <h2 class="h2_frm">구독결제 정보</h2>
    <?php echo $pg_anchor; ?>
    <div class="local_desc02 local_desc">
        <p>구독서비스 정보는 좌측메뉴 > 구독상품 관리에서 수정할 수 있습니다.</p>
    </div>
    <form name="form_batch_info" action="./batch_update.php" method="post" autocomplete="off">
        <input type="hidden" name="od_id" value="<?php echo $od_id; ?>">

        <div class="compare_wrap">
            <section id="anc_sodr_paymo" class="compare_left">
                <h3>구독결제 정보</h3>
                <div class="tbl_frm01">
                    <table>
                        <caption>구독결제 상세정보 수정</caption>
                        <colgroup>
                            <col class="grid_3">
                            <col>
                        </colgroup>
                        <tbody>
                            <tr>
                                <th>주문번호</th>
                                <td><?php echo $batch_info['display_od_id'] ?></td>
                                <th>회원정보</label></th>
                                <td><?php echo $batch_info['mb_side_view'] ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="od_refund_price">자동결제 키</label></th>
                                <td>
                                    <span id="display_batch_key">
                                        <?php echo $batch_info['display_batch_key'] ?>
                                    </span>
                                    <button type="button" id="btn_batch_key" class="btn btn_02">변경</button>
                                </td>
                                <th scope="row"><label for="od_refund_price">다음 결제 예정일</label></th>
                                <td>
                                    -
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="od_refund_price">결제시작일</label></th>
                                <td><?php echo $batch_info['start_date'] ?></td>
                                <th scope="row"><label for="od_refund_price">결제종료일</label></th>
                                <td><?php echo $batch_info['display_end_date'] ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="od_refund_price">결제진행 상태</label></th>
                                <td colspan="3">
                                    <select name="status">
                                        <option value="1" <?php echo $batch_info['status'] == "1" ? "selected" : ""?>>진행 중</option>
                                        <option value="0" <?php echo $batch_info['status'] == "0" ? "selected" : ""?>>결제종료</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="anc_sodr_chk" class="compare_right">
                <h3>구독서비스 정보</h3>
                <div class="tbl_frm01">
                    <table>
                        <caption>결제상세정보</caption>
                        <colgroup>
                            <col class="grid_1">
                            <col class="grid_3">
                            <col class="grid_1">
                            <col class="grid_3">
                        </colgroup>
                        <tbody>
                            <tr>
                                <th>서비스명</th>
                                <td colspan="3"><?php echo $service['service_name'] ?></td>
                            </tr>
                            <tr>
                                <th>게시판</th>
                                <td><?php echo $service['bo_subject'] ?></td>
                                <th>구독만료 기한</th>
                                <td><?php echo $service['display_expiration']; ?></td>
                            </tr>
                            <tr>
                                <th>가격</th>
                                <td <?php echo empty($price_schedule) ? "colspan='3'" : ""?>><?php echo number_format($service['price']); ?>원</td>
                                <?php if (isset($price_schedule)) { ?>
                                <th>
                                    변경예정 가격
                                    <br>
                                    (<?php echo $price_schedule['display_apply_date']?>)
                                </th>
                                <td><?php echo number_format($price_schedule['price']) ?>원</td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <th>결제주기</th>
                                <td <?php echo empty($date_schedule) ? "colspan='3'" : ""?>><?php echo $service['display_recurring'] ?></td>
                                <?php if (isset($date_schedule)) { ?>
                                <th>
                                    변경예정 결제주기
                                    <br>
                                    (<?php echo $date_schedule['display_apply_date']?>)
                                </th>
                                <td><?php echo $date_schedule['recurring'] ?></td>
                                <?php } ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        
        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="결제정보 수정" class="btn_submit btn">
            <a href="./batch_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
        </div>
    </form>
</section>

<section id="anc_sodr_pay">
    <h2 class="h2_frm">결제내역</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>주문결제 내역</caption>
            <thead>
                <tr>
                    <th scope="col">회차</th>
                    <th scope="col">결제금액</th>
                    <th scope="col">결제번호</th>
                    <th scope="col">결제 배치키</th>
                    <th scope="col">카드명</th>
                    <th scope="col">결과</th>
                    <th scope="col">결과메시지</th>
                    <th scope="col">결제일</th>
                    <th scope="col">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payment_list as $payment) { ?>
                <tr>
                    <td><?php echo $payment['display_payment_count'] ?></td>
                    <td><?php echo $payment['display_amount'] ?></td>
                    <td><?php echo $payment['tno'] ?></td>
                    <td><?php echo $payment['display_batch_key'] ?></td>
                    <td><?php echo $payment['card_name'] ?></td>
                    <td style="color:<?php echo $payment['display_res_cd_color']?>">
                        <strong><?php echo $payment['display_res_cd'] ?></strong>
                    </td>
                    <td><?php echo $payment['res_msg'] ?></td>
                    <td><?php echo $payment['date'] ?></td>
                    <td>
                    <?php if (!$payment_success[$payment['payment_count']]) { ?>
                        <button type="button" name="btn_payment" data-id="<?php echo $payment['id']?>" data-count="<?php echo $payment['payment_count']?>" class="btn btn_02 btn_payment">결제</button>
                    <?php } ?>
                    <?php if ($payment['is_btn_refund']) { ?>
                        <button type="button" id="btn_batch_key" class="btn btn_01">환불</button>
                    <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="9">총 결제금액 : <?php echo number_format($total_amount) ?>원</td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<form name="form_batch_key" id="form_batch_key" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ordr_idxx" class="w200" value="<?php echo $od_id ?>" maxlength="40" />

    <input type="hidden" name="site_cd"         value="<?php echo site_cd ?>" />
    <input type="hidden" name="kcpgroup_id"     value="<?php echo kcpgroup_id ?>" />
    <!-- 가맹점 정보 설정-->
    <input type="hidden" name="site_name"      value="TEST SITE" />
    <!-- 상품제공기간 설정 -->
    <input type="hidden" name="good_expr"      value="2:1m"/>
    <!-- 결제 방법 : 인증키 요청-->
    <input type="hidden" name="pay_method"     value="AUTH:CARD" />
    <!-- 인증 방식 : 공동인증-->
    <input type="hidden" name="card_cert_type" value="BATCH" />
    <!-- 배치키 발급시 주민번호 입력을 결제창 안에서 진행 -->
    <input type='hidden' name='batch_soc'      value="Y"/>
    <!-- 
        ※필수 항목
        표준웹에서 값을 설정하는 부분으로 반드시 포함되어야 합니다값을 설정하지 마십시오
    -->
    <input type="hidden" name="module_type"     value="01"/>
    <input type="hidden" name="res_cd"          value=""/>
    <input type="hidden" name="res_msg"         value=""/>
    <input type="hidden" name="enc_info"        value=""/>
    <input type="hidden" name="enc_data"        value=""/>
    <input type="hidden" name="tran_cd"         value=""/>
    <input type="hidden" name="buyr_name"       value="<?php echo $batch_info['mb_name']?>"/>

    <!-- 주민번호 S / 사업자번호 C 픽스 여부 -->
    <!-- <input type='hidden' name='batch_soc_choice' value='' /> -->

    <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
    <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
    <!-- <input type='hidden' name='batch_cardno_return_yn'  value='Y'> -->

    <!-- batch_cardno_return_yn 설정시 결제창에서 리턴 -->
    <!-- <input type='hidden' name='card_mask_no'			  value=''> -->
</form>

<script type="text/javascript" src="https://testpay.kcp.co.kr/plugin/payplus_web.jsp"></script>
<script>
function m_Completepayment( frm_mpi, closeEvent ) 
{
    var frm = document.form_batch_key; 

    if (frm_mpi.res_cd.value == "0000" )
    {
        GetField(frm, frm_mpi); 
        
        let data = new FormData(document.getElementById('form_batch_key'));
        let queryString = new URLSearchParams(data).toString();

        $.ajax({
            url : "./ajax.get_batch_key.php",
            type: "POST",
            data: queryString,
            success: function(data) {
                if (data) {
                    console.log(data);
                    // Set Data
                    let result = JSON.parse(data);

                    if (result.res_cd == "0000") {
                        alert("자동결제 키가 변경되었습니다.");
                        document.querySelector("#display_batch_key").innerHTML = result.display_batch_key;
                    } else {
                        alert("[" + result.res_cd + "]" + result.res_msg);
                    }
                    
                } else {
                    alert("잠시 후에 시도해주세요.");
                }
            },
            error: function() {
                alert("에러 발생");
            }
        });
    } else {
        setTimeout("alert( \"[" + frm_mpi.res_cd.value + "]" + frm_mpi.res_msg.value + "\");", 1000);
    }
    closeEvent();
}

$(function() {
    let form            = document.querySelector("#form_batch_key");
    let btn_batch_key   = document.querySelector("#btn_batch_key");
    let btn_payment     = $("button[name=btn_payment]");

    /* 표준웹 실행 */
    btn_batch_key.onclick = function(){
        try {
            KCP_Pay_Execute(form);
        } catch (e) {
            /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
        }
    };
    /* 실패한 결제이력 결제처리 */
    btn_payment.on('click', function(){
        if (confirm(this.dataset.count + "회차 결제를 진행하시겠습니까?")) {
            $.ajax({
                url : "./ajax.order_batch.php",
                type: "POST",
                data: {"id" : this.dataset.id, "ordr_idxx" : '<?php echo $od_id ?>'},
                success: function(data) {
                    if (data) {
                        console.log(data);
                        // Set Data
                        let result = JSON.parse(data);
                        if (result.result_code == "0000") {
                            // 성공
                            alert(result.result_msg);
                            location.reload();
                        } else {
                            // 실패
                            alert("[" + result.result_code + "]" + result.result_msg);
                        }
                    } else {
                        alert("잠시 후에 시도해주세요.");
                    }
                },
                error: function() {
                    alert("에러 발생");
                }
            });
        }
    });
});
</script>
<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
