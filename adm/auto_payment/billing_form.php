<?php
$sub_menu = '800940';
include_once './_common.php';

$g5['title'] = "구독결제 수정";
include_once G5_ADMIN_PATH . '/admin.head.php';
include_once G5_PLUGIN_PATH . '/jquery-ui/datepicker.php';

auth_check_menu($auth, $sub_menu, 'w');

/** 변수 선언 */
$billing            = new Billing($billing_conf['bc_pg_code']);
$service_model      = new BillingServiceModel();
$information_model  = new BillingInformationModel();
$price_model        = new BillingServicePriceModel();
$history_model      = new BillingHistoryModel();
$cancel_model       = new BillingCancelModel();

$od_id = isset($_REQUEST['od_id']) ? safe_replace_regex($_REQUEST['od_id'], 'od_id') : '';

/** 데이터 출력 */
/* 구독정보 */
// 구독정보 조회
$billing_info = $information_model->selectOneByOrderId($od_id);

// 결과처리
$service_id = $billing_info['service_id'];

$billing_info['mb_side_view']         = get_sideview($billing_info['mb_id'], get_text($billing_info['mb_name']), $billing_info['mb_email'], '');
$billing_info['display_end_date']     = (!is_null($billing_info['end_date']) && $billing_info['end_date'] != '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($billing_info['end_date'])) : "";
$billing_info['display_status']       = $billing_info['status'] == "1" ? "구독 중" : "구독 종료";
$billing_info['display_billing_key']  = $billing->displayBillKey($billing_info['billing_key']);
$billing_info['display_next_payment'] = date('Y-m-d', strtotime($billing_info['next_payment_date']));
$billing_info['display_od_id']        = $billing_info['od_id'];
$billing_info['display_event_price']  = number_format((int)$billing_info['event_price']) . "원";
$billing_info['display_event_date']   = (!is_null($billing_info['event_expiration_date']) && $billing_info['event_expiration_date'] != '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($billing_info['event_expiration_date'])) : "";

/* 구독상품 */
// 구독상품 정보 조회
$service = $service_model->selectOneById($service_id);

// 결과처리
$service['display_expiration']  = ($dsExp = $billing->displayExpiration($service)) ? '결제일로부터 ' . $dsExp . ' 이후' : '없음';
$service['display_recurring']   = ($dsRec = $billing->displayRecurring($service)) ? $dsRec : '없음';
$service['price']               = $price_model->selectCurrentPrice($service_id);

// 변경예정 가격 최근 1건 조회
$price_schedule = $price_model->selectScheduledChangePriceInfo($service_id);
if (isset($price_schedule)) {
    $price_schedule['display_price']    = number_format($price_schedule['price']);
    $price_schedule['display_date']     = date('Y-m-d', strtotime($price_schedule['application_date'])) . " 반영";
}

/* 결제내역 */
$total_amount = 0;          // 총 결제금액
$payment_success = array(); // 회차별 결제성공여부

// 결제내역 조회
$history_list = $history_model->selectListByOrderId($od_id);

// 결과처리
foreach ($history_list as $i => $history) {
    $count = $history['payment_count'];
    $cancel_amount = $cancel_model->selectTotalCancelAmount($history['payment_no']);

    $history_list[$i]['display_payment_count']  = $count . "회차";
    $history_list[$i]['display_amount']         = number_format($history['amount']) . "원";
    $history_list[$i]['display_period']         = ($history['result_code'] == "0000" ? $history['period'] : '');
    $history_list[$i]['is_btn_cancel']          = false;
    $history_list[$i]['display_cancel_amount']  = number_format($cancel_amount) . '원';

    if ($history['total_cancel'] == $history['amount']) {
        $history_list[$i]['display_result'] = "환불";
        $history_list[$i]['display_result_color']   = "#AAAAAA";
    } else if ($history['total_cancel'] != null) {
        $history_list[$i]['display_result'] = "부분 취소";
        $history_list[$i]['display_result_color']   = "#AAAAAA";
        $history_list[$i]['is_btn_cancel'] = true;
    } else if ($history['result_code'] == "0000") {
        $history_list[$i]['display_result'] = "성공";
        $history_list[$i]['display_result_color']   = "#53C14B";
        $history_list[$i]['is_btn_cancel'] = true;
    } else {
        $history_list[$i]['display_result'] = "실패";
        $history_list[$i]['display_result_color']   = "#FF0000";
    }

    if (!isset($payment_success[$count])) {
        $payment_success[$count] = false;
    }

    if ($history['result_code'] == "0000") {
        $payment_success[$count] = true;
        $total_amount += ($history['amount'] - $cancel_amount);
    }
}

// etc
$qstr = $qstr . '&amp;sca=' . $sca . '&amp;page=' . $page;
$pg_anchor = '<ul class="anchor">
<li><a href="#anc_billing_info">구독결제 정보</a></li>
<li><a href="#anc_billing_history">결제내역</a></li>
</ul>';
?>
<style>
    .tbl_frm01 th {
        width: 100px;
    }
</style>
<section id="anc_billing_info">
    <h2 class="h2_frm">구독결제 정보</h2>
    <?php echo $pg_anchor; ?>

    <div class="local_desc02 local_desc">
        <p>구독서비스 정보는 좌측메뉴 > 구독상품 관리에서 수정할 수 있습니다.</p>
    </div>

    <form name="form_billing_info" action="./billing_update.php" method="post" autocomplete="off">
        <input type="hidden" name="od_id" value="<?php echo $od_id; ?>">

        <div class="compare_wrap">
            <section class="compare_left">
                <h3>구독결제 정보</h3>
                <div class="tbl_frm01">
                    <table>
                        <caption>구독결제 상세정보 수정</caption>
                        <colgroup>
                            <col class="grid_1">
                            <col class="grid_3">
                            <col class="grid_1">
                            <col class="grid_3">
                        </colgroup>
                        <tbody>
                            <tr>
                                <th>주문번호</th>
                                <td><?php echo $billing_info['display_od_id'] ?></td>
                                <th>회원정보</label></th>
                                <td><?php echo $billing_info['mb_side_view'] ?></td>
                            </tr>
                            <tr>
                                <th scope="row">자동결제 키</th>
                                <td>
                                    <span id="display_billing_key">
                                        <?php echo $billing_info['display_billing_key'] ?>
                                    </span>
                                    <button type="button" id="btn_billing_key" class="btn btn_02">변경</button>
                                </td>
                                <th scope="row"><label for="next_payment_date">다음 결제 예정일</label></th>
                                <td>
                                    <input type="text" class="frm_input date_format" name="next_payment_date" value="<?php echo $billing_info['display_next_payment'] ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">결제시작일</th>
                                <td><?php echo $billing_info['start_date'] ?></td>
                                <th scope="row"><label for="end_date">결제종료일</label></th>
                                <td>
                                    <input type="text" class="frm_input date_format" name="end_date" value="<?php echo $billing_info['display_end_date'] ?>">
                                </td>
                            </tr>
                            <?php if (!empty($billing_info['display_event_date'])) { ?>
                            <tr>
                                <th scope="row">이벤트 가격</th>
                                <td><?php echo $billing_info['display_event_price'] ?></td>
                                <th scope="row">이벤트 종료일</th>
                                <td><?php echo $billing_info['display_event_date'] ?></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th scope="row"><label for="status">구독 상태</label></th>
                                <td colspan="3">
                                    <select name="status">
                                        <option value="1" <?php echo $billing_info['status'] == "1" ? "selected" : "" ?>>구독 중</option>
                                        <option value="0" <?php echo $billing_info['status'] == "0" ? "selected" : "" ?>>구독 종료</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="compare_right">
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
                                <td colspan="3"><?php echo $service['name'] ?></td>
                            </tr>
                            <tr>
                                <th>게시판</th>
                                <td><?php echo $service['bo_subject'] ?></td>
                                <th>구독만료 기한</th>
                                <td><?php echo $service['display_expiration']; ?></td>
                            </tr>
                            <tr>
                                <th>가격</th>
                                <td <?php echo (!isset($price_schedule)) ? "colspan='3'" : "" ?>><?php echo number_format($service['price']); ?>원</td>
                                <?php if (isset($price_schedule)) { ?>
                                    <th>
                                        변경예정 가격
                                        <br>
                                        (<?php echo $price_schedule['display_date'] ?>)
                                    </th>
                                    <td><?php echo number_format($price_schedule['price']) ?>원</td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <th>결제주기</th>
                                <td colspan="3"><?php echo $service['display_recurring'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="결제정보 수정" class="btn_submit btn">
            <a href="./billing_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
        </div>
    </form>
</section>

<section id="anc_billing_history">
    <h2 class="h2_frm">결제내역</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>주문결제 내역</caption>
            <thead>
                <tr>
                    <th scope="col" style="width:10%;">회차</th>
                    <th scope="col" style="width:12%;">결제번호</th>
                    <th scope="col" style="width:8%;">결제금액</th>
                    <th scope="col" style="width:8%;">환불금액</th>
                    <th scope="col" style="width:10%;">카드명</th>
                    <th scope="col" style="width:10%;">결제상태</th>
                    <th scope="col" style="width:12%;">결제일</th>
                    <th scope="col" style="width:12%;">유효기간</th>
                    <th scope="col" style="width:8%;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_list as $history) { ?>
                    <tr>
                        <td class="td_mng_l">
                            <div><?php echo $history['display_payment_count'] ?></div>
                        </td>
                        <td>
                            <span class="payment_no" style="text-decoration: underline; cursor: pointer;" data-id="<?php echo $history['id'] ?>">
                                <?php echo $history['payment_no'] ?>
                            </span>
                        </td>
                        <td style="color: green;"><?php echo $history['display_amount'] ?></td>
                        <td style="color: red;"><?php echo $history['display_cancel_amount'] ?></td>
                        <td><?php echo $history['card_name'] ?></td>
                        <td style="color:<?php echo $history['display_result_color'] ?>">
                            <strong><?php echo $history['display_result'] ?></strong>
                        </td>
                        <td class="td_mng_l">
                            <?php echo $history['payment_date'] ?>
                        </td>
                        <td class="td_mng_l">
                            <?php echo $history['display_period'] ?>
                        </td>
                        <td>
                            <?php if (!$payment_success[$history['payment_count']]) { ?>
                                <button type="button" name="btn_payment" data-id="<?php echo $history['id'] ?>" data-count="<?php echo $history['payment_count'] ?>" class="btn btn_02 btn_payment">결제</button>
                            <?php } ?>
                            <?php if ($history['is_btn_cancel']) { ?>
                                <button type="button" class="btn btn_01 btn_cancel" data-id="<?php echo $history['id'] ?>" data-service_id="<?php echo $service_id ?>">환불</button>
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

<form name="form_billing_key" id="form_billing_key" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ordr_idxx" class="w200" value="<?php echo $od_id ?>" maxlength="40" />

    <input type="hidden" name="site_cd" value="<?php echo site_cd ?>" />
    <input type="hidden" name="kcpgroup_id" value="<?php echo kcpgroup_id ?>" />
    <!-- 가맹점 정보 설정-->
    <input type="hidden" name="site_name" value="<?php echo $config['cf_title'] ?>" />
    <!-- 상품제공기간 설정 -->
    <input type="hidden" name="good_expr" value="2:1m" />
    <!-- 결제 방법 : 인증키 요청-->
    <input type="hidden" name="pay_method" value="AUTH:CARD" />
    <!-- 인증 방식 : 공동인증-->
    <input type="hidden" name="card_cert_type" value="BATCH" />
    <!-- 배치키 발급시 주민번호 입력을 결제창 안에서 진행 -->
    <input type='hidden' name='batch_soc' value="Y" />
    <!-- 
        ※필수 항목
        표준웹에서 값을 설정하는 부분으로 반드시 포함되어야 합니다값을 설정하지 마십시오
    -->
    <input type="hidden" name="module_type" value="01" />
    <input type="hidden" name="res_cd" value="" />
    <input type="hidden" name="res_msg" value="" />
    <input type="hidden" name="enc_info" value="" />
    <input type="hidden" name="enc_data" value="" />
    <input type="hidden" name="tran_cd" value="" />
    <input type="hidden" name="buyr_name" value="<?php echo $billing_info['mb_name'] ?>" />

    <!-- 주민번호 S / 사업자번호 C 픽스 여부 -->
    <!-- <input type='hidden' name='batch_soc_choice' value='' /> -->

    <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
    <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
    <input type='hidden' name='batch_cardno_return_yn' value='L'>

    <!-- batch_cardno_return_yn 설정시 결제창에서 리턴 -->
    <input type='hidden' name='card_mask_no' value=''>

    <input type="hidden" name="mb_id" value="<?php echo $billing_info['mb_id'] ?>" />
</form>

<?php
if ($billing_conf['bc_kcp_is_test'] == "0") {
    echo '<script type="text/javascript" src="https://pay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
} else {
    echo '<script type="text/javascript" src="https://testpay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
}
?>
<script>
    function m_Completepayment(frm_mpi, closeEvent) {
        var frm = document.form_billing_key;

        if (frm_mpi.res_cd.value == "0000") {
            GetField(frm, frm_mpi);

            let data = new FormData(document.getElementById('form_billing_key'));
            let queryString = new URLSearchParams(data).toString();

            $.ajax({
                url: "./ajax.get_billing_key.php",
                type: "POST",
                data: queryString,
                success: function(data) {
                    if (data) {
                        let result = JSON.parse(data);

                        if (result.result_code == "0000") {
                            alert("자동결제 키가 변경되었습니다.");
                            document.querySelector("#display_billing_key").innerHTML = result.display_billing_key;
                        } else {
                            alert("[" + result.result_code + "]" + result.result_message);
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
        let form = document.querySelector("#form_billing_key");
        let btn_billing_key = document.querySelector("#btn_billing_key");
        let btn_payment = $("button[name=btn_payment]");

        $('.date_format').datepicker();

        $.datepicker.setDefaults({
            changeMonth: true,
            changeYear: true,
            dateFormat: "yy-mm-dd",
            showButtonPanel: true,
            yearRange: "c-99:c+99",
            maxDate: "+10y"
        })

        /* 표준웹 실행 */
        btn_billing_key.onclick = function() {
            try {
                KCP_Pay_Execute(form);
            } catch (e) {
                /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
            }
        };
        /* 실패한 결제이력 결제처리 */
        btn_payment.on('click', function() {
            if (confirm(this.dataset.count + "회차 결제를 진행하시겠습니까?")) {
                $.ajax({
                    url: "./ajax.request_billing.php",
                    type: "POST",
                    data: {
                        "id": this.dataset.id,
                        "od_id": '<?php echo $od_id ?>',
                        "mb_id": '<?php echo $billing_info['mb_id'] ?>'
                    },
                    success: function(data) {
                        if (data) {
                            let result = JSON.parse(data);

                            if (result.result_code == "0000") {
                                alert(result.result_message);
                                location.reload();
                            } else {
                                alert("[" + result.result_code + "]" + result.result_message);
                            }
                        } else {
                            alert("잠시 후에 시도해주세요.");
                        }
                    },
                    error: function(result) {
                        if (result.responseJSON.msg) {
                            alert(result.responseJSON.msg);
                        } else {
                            alert("에러 발생");
                        }
                    }
                });
            }
        });

        $(".btn_cancel").on("click", function() {
            var url = "./cancel_popup.php?id=" + $(this).data('id') + "&service_id=" + $(this).data('service_id');
            window.open(url, "cancel_billing", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
            return false;
        })

        $(".payment_no").on("click", function() {
            var url = "./payment_detail.php?id=" + $(this).data('id');
            window.open(url, "billing_history_Detail", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
            return false;
        })
    });
</script>
<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
