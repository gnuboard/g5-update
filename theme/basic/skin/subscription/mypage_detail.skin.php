<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once dirname(__FILE__) . '/mypage_head.skin.php';

$od_id = isset($_GET['od_id']) ? safe_replace_regex($_GET['od_id'], 'od_id') : '';
if (empty($od_id)) {
    alert('주문번호가 없습니다.');
}

$convertYMDUnit1 = $billing->getUnitArray('prefix');
$convertYMDUnit2 = $billing->getUnitArray('period');

$info = get_myservice_info($od_id);
if ($info === false) {
    alert('구독 정보가 없습니다.');
}

// 결과처리
$info['display_bill_key']   = $billing->displayBillKey($info['billing_key']);
$info['display_next_date']  = date('Y-m-d', strtotime($info['next_payment_date']));
$info['display_recurring']  = strtr($info['recurring'], $convertYMDUnit2);
$info['display_start_date'] = date('Y-m-d', strtotime($info['start_date']));
$info['display_end_date']   = strtotime($info['end_date']) > 0 ? date('Y-m-d', strtotime($info['end_date'])) : '';
$info['display_event_date'] = $billing->convertDateFormat($info['event_expiration_date']);

$info['display_status']     = $info['status'] == '1' ? '구독 중' : '구독 종료';
$info['display_price']      = number_format($info['price']) . "원";
$info['display_event_price'] = number_format((int)$info['event_price']) . "원";

$payment_list = get_myservice_history($od_id, $start_page, $page_rows);
if ($payment_list === false) {
    $payment_list = array();
}
foreach ($payment_list as $key => $row) {
    $payment_list[$key]['display_payment_count'] = $row['payment_count'] . "회차";
    $payment_list[$key]['display_amount'] = number_format($row['amount']) . "원";
    $payment_list[$key]['display_period'] = ($row['result_code'] == "0000" ? $row['period'] : '');

    // 환불금액 표시
    $payment_list[$key]['cancel_amount'] = $billing_cancel->selectTotalCancelAmount($row['payment_no']);
    if ($row['total_cancel'] == $row['amount']) {
        $payment_list[$key]['display_result'] = "환불완료";
        $payment_list[$key]['display_result_color']   = "#AAAAAA";
    } else if ($row['total_cancel'] != null) {
        $payment_list[$key]['display_result'] = "부분 취소";
        $payment_list[$key]['display_result_color']   = "#AAAAAA";
        $payment_list[$key]['is_btn_cancel'] = true;
    } else if ($row['result_code'] == "0000") {
        $payment_list[$key]['display_result'] = "성공";
        $payment_list[$key]['display_result_color']   = "#53C14B";
        $payment_list[$key]['is_btn_cancel'] = true;
    } else {
        $payment_list[$key]['display_result'] = "실패";
        $payment_list[$key]['display_result_color']   = "#FF0000";
    }
}
?>
<div>
    <h2 id="container_title"><span title="결제 정보">구독&결제 정보</span></h2>

    <div class="compare_wrap">
        <section class="compare_left">
            <div class="tbl_frm01">
                <table>
                    <colgroup>
                        <col style="width:15%;">
                        <col style="width:35%;">
                        <col style="width:15%;">
                        <col style="width:35%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <th>주문번호</th>
                            <td colspan="3"><?php echo $od_id ?></td>
                        </tr>
                        <tr>
                            <th>서비스명</th>
                            <td><?php echo $info['name'] ?></td>
                            <th>이용가능 게시판</th>
                            <td><?php echo $info['bo_subject'] ?></td>
                        </tr>
                        <tr>
                            <th>가격</th>
                            <td <?php echo empty($info['display_event_date']) ? 'colspan="3"' : '' ?>><?php echo $info['display_price'] ?> </td>
                            <?php if (!empty($info['display_event_date'])) { ?>
                                <th scope="row">이벤트 가격</th>
                                <td><?php echo $info['display_event_price'] . " ({$info['display_event_date']} 까지)"; ?></td>
                            <?php } ?>
                        </tr>
                        <?php if ($info['status'] == '1') { ?>
                            <tr>
                                <th>결제수단</th>
                                <td>
                                    <span id="display_billing_key" style="line-height: 2.6rem;"><?php echo $info['display_bill_key']; ?></span>
                                    <button type="button" class="btn_frmline" id="btn_batch_key">결제수단 변경</button>
                                </td>
                                <th>다음 결제일</th>
                                <td><?php echo $info['display_next_date']; ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <th><label for="od_refund_price">결제 시작일</label></th>
                            <td><?php echo $info['display_start_date'] ?></td>
                            <th><label for="od_refund_price">결제 종료일</label></th>
                            <td><?php echo $info['display_end_date'] ?></td>
                        </tr>
                        <tr>
                            <th>구독상태</th>
                            <td colspan="3">
                                <span style="line-height: 2.6rem;"><?php echo $info['display_status'] ?></span>
                                <?php if ($info['status'] == '1') { ?>
                                    <button type="button" class="btn_frmline btn_cancel" data-od_id="<?php echo $od_id ?>">구독 취소</button>
                                <?php } ?>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<div>
    <h2 id="container_title"><span title="결제 내역">결제 내역</span></h2>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>내역</caption>
            <thead>
                <tr>
                    <th scope="col" width="20%">회차</th>
                    <th scope="col" width="15%">결제금액</th>
                    <th scope="col" width="10%">카드명</th>
                    <th scope="col" width="15%">결제일</th>
                    <th scope="col" width="15%">결과</th>
                    <th scope="col" width="25%">기타</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payment_list) > 0) { ?>
                    <?php foreach ($payment_list as $payment) { ?>
                        <tr style="text-align: center;">
                            <td>
                                <?php echo $payment['display_payment_count'] ?>
                                <br>
                                <?php echo $payment['display_period'] ?>
                            </td>
                            <td><?php echo $payment['display_amount'] ?></td>
                            <td><?php echo $payment['card_name'] ?></td>
                            <td><?php echo $payment['payment_date'] ?></td>
                            <td style="color:<?php echo $payment['display_result_color'] ?>">
                                <?php echo $payment['display_result'] ?>
                            </td>
                            <td>
                                <?php echo $payment['cancel_amount'] > 0 ? number_format($payment['cancel_amount']) . '원 환불' : '' ?>
                                <?php echo $payment['result_code'] !== '0000' ? $payment['result_message'] : '' ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="empty_table">게시물이 없습니다.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<form name="form_batch_key" id="form_batch_key" method="post" enctype="multipart/form-data">
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
    <!-- 배치키 발급시 주민번호 입력을 결제창 안에서 진행 (고정값)-->
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
    <input type="hidden" name="buyr_name" value="<?php echo $member['mb_name'] ?>" />

    <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
    <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
    <input type='hidden' name='batch_cardno_return_yn'  value='L'>

    <!-- batch_cardno_return_yn 설정시 결제창에서 리턴 -->
    <input type='hidden' name='card_mask_no'			  value=''>
</form>
<?php
if ($billing_conf['bc_kcp_is_test'] == "0") {
    echo '<script type="text/javascript" src="https://pay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
} else {
    echo '<script type="text/javascript" src="https://testpay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
}
?>
<script>
    $(function() {
        let btn_batch_key = document.querySelector("#btn_batch_key");

        /* 표준웹 실행 */
        btn_batch_key.onclick = function() {
            let form = document.querySelector("#form_batch_key");

            try {
                KCP_Pay_Execute(form);
            } catch (e) {
                /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
            }
        };

        $('.btn_cancel').on('click', function() {
            if (confirm("해당 서비스의 구독을 취소하시겠습니까?")) {
                let od_id = $(this).data('od_id');
                const data = {
                    'w': 'cancel',
                    'od_id': od_id
                };
                $.ajax(g5_bbs_url + '/subscription/ajax.mypage.php', {
                    type: 'post',
                    data: JSON.stringify(data),
                    contentType: false,
                    success: function(data) {
                        console.log(data);
                        if (data === true) {
                            alert('해당상품의 구독이 취소되었습니다.');
                            location.reload();
                        } else {
                            alert('구독취소 오류');
                        }
                    },
                    error: function(e) {

                    }
                })
            }
        });
    });

    function m_Completepayment(frm_mpi, closeEvent) {
        let frm = document.form_batch_key;

        if (frm_mpi.res_cd.value === "0000") {
            GetField(frm, frm_mpi);

            let data = new FormData(document.getElementById('form_batch_key'));
            let queryString = new URLSearchParams(data).toString();

            $.ajax({
                url: "<?php echo G5_ADMIN_URL ?>/auto_payment/ajax.get_billing_key.php",
                type: "POST",
                data: queryString,
                success: function(res) {
                    if (res) {
                        if (res.result_code === "0000") {
                            alert("자동결제 키가 변경되었습니다.");
                            document.querySelector("#display_billing_key").innerHTML = res.display_billing_key;
                        } else {
                            if (res.result_message == undefined) {
                                //로그아웃이 된 경우에도 발생.
                                alert("카드 등록에 실패했습니다");
                                location.reload();
                            }

                            alert('카드 등록에 실패했습니다. ' + res.result_message);
                            console.log("[" + res.result_code + "]" + res.result_message);
                        }
                    } else {
                        alert("카드 등록에 실패했습니다");
                        location.reload();
                    }
                },
                error: function(res) {
                    let message = '';
                    if (res.responseJSON != undefined && res.responseJSON.result_message != undefined) {
                        message = res.responseJSON.result_message;
                    }
                    alert("카드 등록에 실패했습니다" + message);
                }
            });
        } else {
            setTimeout("alert( \"[" + frm_mpi.res_cd.value + "]" + frm_mpi.res_msg.value + "\");", 1000);
        }
        closeEvent();
    }
</script>
<?php
include_once G5_PATH . '/tail.php';
