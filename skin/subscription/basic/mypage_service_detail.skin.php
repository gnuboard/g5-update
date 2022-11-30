<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once dirname(__FILE__) . '/../../../common.php';
require_once G5_PATH . '/head.php';
require_once G5_PATH . '/head.sub.php';

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head
require_once(G5_BBS_PATH . '/subscription/mypage.php'); //TODO subscription head

require_once G5_PATH . '/lib/billing/G5Mysqli.php';
require_once G5_PATH . '/lib/billing/KcpBatch.php';
require_once G5_PATH . '/lib/billing/Billing.php';
require_once G5_PATH . '/lib/billing/BillingInterface.php';
require_once G5_PATH . '/lib/billing/kcp/G5BillingKcp.php';
require_once G5_PATH . '/lib/billing/toss/G5BillingToss.php';

$billing = new Billing('kcp');

if (empty($is_member)) {
    alert('로그인 하셔야 됩니다.', G5_BBS_URL . '/login.php');
}

$convertYMDUnit1 = array('y' => '연간', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');


$od_id = isset($_GET['od_id']) ? safe_replace_regex($_GET['od_id'], 'od_id') : '';

echo $sql = "SELECT
            bs.name,
            CONCAT(recurring, recurring_unit) AS recurring,
            (SELECT price FROM g5_billing_service_price price WHERE bi.service_id = price.service_id AND price.application_date <= NOW() ORDER BY application_date DESC LIMIT 1) AS price,
            board.bo_subject,
            bi.billing_key,
            bi.start_date,
            bi.end_date,
            bi.status,
            bi.next_payment_date
        FROM g5_billing_information bi
        LEFT JOIN g5_billing_service bs ON bi.service_id = bs.service_id
        LEFT JOIN g5_board board ON bs.service_table = board.bo_table
        WHERE
            bi.od_id = '{$od_id}'";
$info = sql_fetch($sql);
// 결과처리
$info['display_bill_key']   = $billing->displayBillKey($info['billing_key']);
$info['display_next_date']  = date('Y-m-d', strtotime($info['next_payment_date']));
$info['display_recurring']  = strtr($info['recurring'], $convertYMDUnit2);
$info['display_period']     = strtotime($info['end_date']) > 0 ? date('Y-m-d', strtotime($info['start_date'])) . ' ~ ' . date('Y-m-d', strtotime($info['end_date'])) : '';
$info['display_status']     = $info['status'] == '1' ? '구독 중' : '구독 종료';

$payment_list = array();
$sql = "SELECT 
            *,
            CONCAT(DATE_FORMAT(payment_date, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(expiration_date, '%Y-%m-%d')) AS period
        FROM g5_batch_payment 
        WHERE od_id = {$od_id} 
        ORDER BY payment_count DESC, payment_date DESC";
$result = sql_query($sql);
for ($i = 0; $row = sql_fetch_array($result); $i++) {
    $payment_list[$i] = $row;
    $payment_list[$i]['display_payment_count']  = $row['payment_count'] . "회차";
    $payment_list[$i]['display_amount']         = number_format($row['amount']) . "원";
    $payment_list[$i]['display_res_cd']         = ($row['res_cd'] == "0000" ? "성공" : "실패");
    $payment_list[$i]['display_res_cd_color']   = ($row['res_cd'] == "0000" ? "#53C14B" : "#FF0000");
    $payment_list[$i]['display_period']         = ($row['res_cd'] == "0000" ? $row['period'] : '');
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
                            <th>서비스명</th>
                            <td><?php echo $info['name'] ?></td>
                            <th>이용가능 게시판</th>
                            <td><?php echo $info['bo_subject'] ?></td>
                        </tr>
                        <tr>
                            <th>가격</th>
                            <td><?php echo number_format($info['price']) . "원 ({$info['display_recurring']})" ?> </td>
                            <th>구독상태</th>
                            <td>
                                <span style="line-height: 2.6rem;"><?php echo $info['display_status'] ?></span>
                                <?php if ($info['status'] !== '0') { ?>
                                <button type="button" class="btn_frmline btn_cancel" data-od_id="<?=$od_id?>">구독 취소</button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php if ($info['status'] !== '0') { ?>
                        <tr>
                            <th>결제수단</th>
                            <td>
                                <span id="display_batch_key" style="line-height: 2.6rem;"><?php echo $info['display_bill_key'] ; ?></span>
                                <button type="button" class="btn_frmline" id="btn_batch_key">결제수단 변경</button>
                            </td>
                            <th>다음 결제일</th>
                            <td><?php echo $info['display_next_date']; ?></td>
                        </tr>
                        <?php } ?>
                        <?php if ($info['display_period'] != '') { ?>
                        <tr>
                            <th><label for="od_refund_price">결제 기간</label></th>
                            <td colspan="3"><?php echo $info['display_period'] ?></td>
                        </tr>
                        <?php } ?>
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
                    <th scope="col">회차</th>
                    <th scope="col">결제금액</th>
                    <th scope="col">카드명</th>
                    <th scope="col">결과</th>
                    <th scope="col">결제일</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($payment_list) > 0) { ?>
                <?php foreach($payment_list as $payment) { ?>
                <tr style="text-align: center;">
                    <td>
                        <?php echo $payment['display_payment_count'] ?>
                        <br>
                        <?php echo $payment['display_period'] ?>
                    </td>
                    <td><?php echo $payment['display_amount'] ?></td>
                    <td><?php echo $payment['card_name'] ?></td>
                    <td style="color:<?php echo $payment['display_res_cd_color'] ?>">
                        <?php echo $payment['display_res_cd'] ?>
                    </td>
                    <td><?php echo $payment['payment_date'] ?></td>
                    <td></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">게시물이 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<form name="form_batch_key" id="form_batch_key" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ordr_idxx" class="w200" value="<?php echo $od_id ?>" maxlength="40" />

    <input type="hidden" name="site_cd"         value="<?php echo site_cd ?>" />
    <input type="hidden" name="kcpgroup_id"     value="<?php echo kcpgroup_id ?>" />
    <!-- 가맹점 정보 설정-->
    <input type="hidden" name="site_name"      value="<?php echo $config['cf_title']?>" />
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
    <input type="hidden" name="buyr_name"       value="<?php echo $member['mb_name']?>"/>

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
$(function(){
    let form            = document.querySelector("#form_batch_key");
    let btn_batch_key   = document.querySelector("#btn_batch_key");

    /* 표준웹 실행 */
    btn_batch_key.onclick = function(){
        try {
            KCP_Pay_Execute(form);
        } catch (e) {
            /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
        }
    };

    $('.btn_cancel').on('click', function(){
        if(confirm("해당 서비스의 구독을 취소하시겠습니까?")) {
            let od_id = $(this).data('od_id');
            const data = {
                'w': 'cancel',
                'od_id': od_id
            };
            $.ajax(g5_bbs_url + '/subscription/ajax.mypage.php', {
                type: 'post',
                data: JSON.stringify(data),
                contentType: false,
                success: function (data) {
                    console.log(data);
                    if (data === true) {
                        alert('해당상품의 구독이 취소되었습니다.');
                        location.reload();
                    } else {
                        alert('구독취소 오류');
                    }
                    // let result = JSON.parse(data)
                    // if (res_cd == '0000') {
                    //     alert('해당상품의 구독이 취소되었습니다.');
                    // }
                },
                error: function (e) {

                }
            })
        }
    });
});

function m_Completepayment( frm_mpi, closeEvent ) 
{
    var frm = document.form_batch_key; 

    if (frm_mpi.res_cd.value == "0000" )
    {
        GetField(frm, frm_mpi); 
        
        let data = new FormData(document.getElementById('form_batch_key'));
        let queryString = new URLSearchParams(data).toString();

        $.ajax({
            url : "<?php echo G5_ADMIN_URL ?>/auto_payment/ajax.get_billing_key.php",
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
</script>
<?php
include_once(G5_PATH . '/tail.php');
