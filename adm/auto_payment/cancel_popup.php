<?php
$sub_menu = '800940';
include_once './_common.php';

$g5['title'] = '결제내역 환불';
require_once G5_PATH . '/head.sub.php';

auth_check_menu($auth, $sub_menu, "w");

/* 변수 선언 */
$id         = isset($_GET['id']) ? preg_replace('/[^0-9]/', '', $_GET['id']) : 0;
$service_id = isset($_GET['service_id']) ? preg_replace('/[^0-9]/', '', $_GET['service_id']) : 0;

$html_title = '구독상품 ';
$billing        = new Billing($billing_conf['bc_pg_code']);
$service_model  = new BillingServiceModel();
$history_model  = new BillingHistoryModel();
$cancel_model   = new BillingCancelModel();

$service = $service_model->selectOneById($service_id);
$history = $history_model->selectOneById($id);
$cancel_amount = $cancel_model->selectTotalCancelAmount($history['payment_no']);
$refundable_amount = (int)$history['amount'] - (int)$cancel_amount;


// test
$refund_amount = calcurateRefundAmount($history, $service['base_price']);
function calcurateRefundAmount($history, $base_price) {

    $sTime = strtotime($history['payment_date']);       // 결제시작일
    $eTime = strtotime($history['expiration_date']);    // 유효기간 만료일
    $todayTime = strtotime(date('Y-m-d H:i:s'));        // 오늘

    // 만료일이 지났을 경우 
    if ($todayTime > strtotime($history['expiration_date'])) {
        return 0;
    }
    $diff_day   = ceil(($todayTime - $sTime) / (60 * 60 * 24)); // 사용 일수
    $exp_day    = floor(($eTime - $sTime) / (60 * 60 * 24));    // 유효기간 일수

    // echo "<br>시작날짜 : {$history['payment_date']}<br>";
    // echo "<br>마감날짜 : {$history['expiration_date']}<br>";
    // echo "<br>오늘날짜 : ".date('Y-m-d')."<br>";
    // echo "<br>사용일수 : {$diff_day}<br>";
    // echo "<br>유효일수 : {$exp_day}<br>";
    // echo "<br>기본가격 : {$base_price}<br>";
    // echo "<br>결제가격 : {$history['amount']}<br>";
    // echo "<br><br>환불 금액<br>";

    // 결제금액 - (사용일수 * (기본가격 / 유효일수))
    return $history['amount'] - $diff_day * round($base_price / $exp_day);
}
?>

<div id="menu_frm" class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <form name="cancel_form" id="cancel_form" class="new_win_con" method="post" action="./cancel_update.php" onsubmit="return check_cancel_form(this)">
        <input type="hidden" name="payment_no" value="<?php echo $history['payment_no'] ?>">
        <input type="hidden" name="od_id" value="<?php echo $history['od_id'] ?>">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <div class="new_win_desc">
            <label for="me_type"><h3>서비스 정보</h3></label>
        </div>
        <div id="menu_result">
            <div class="tbl_frm01 tbl_wrap">
                <table>
                    <colgroup>
                        <col class="grid_2" style="width:30%;">
                        <col>
                    </colgroup>
                    <tbody>
                        <tr>
                            <th scope="row"><label for="me_name">서비스 이름 (회차)<strong class="sound_only"> 필수</strong></label></th>
                            <td><?php echo $service['name'] ?> (<?php echo $history['payment_count'] ?>회)</td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="me_name">결제 번호<strong class="sound_only"> 필수</strong></label></th>
                            <td><?php echo $history['payment_no'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        

        <div class="new_win_desc">
            <label for="me_type"><h3>환불 정보</h3></label>
        </div>
        <div id="menu_result">
            <div class="tbl_frm01 tbl_wrap">
                <table>
                    <colgroup>
                        <col class="grid_2" style="width:30%;">
                        <col>
                    </colgroup>
                    <tbody>
                        <tr>
                            <th scope="row"><label for="me_name">환불 사유<strong class="sound_only"> 필수</strong></label></th>
                            <td><textarea style="height: 6em; resize: none;" name="cancel_reason"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="me_name">결제 원 금액<strong class="sound_only"> 필수</strong></label></th>
                            <td><?php echo number_format($history['amount']) ?>원</td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="me_link">환불 가능 금액 <strong class="sound_only"> 필수</strong></label></th>
                            <td><?php echo number_format($refundable_amount) ?>원</td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="me_link">환불금액 <strong class="sound_only"> 필수</strong></label></th>
                            <td>
                                <input type="number" class="frm_input" name="cancel_amount" data-max="<?php echo $refundable_amount ?>">
                                <button type="button" class="btn_02 btn" onclick="input_cancel_amount()">환불금액 계산</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="me_link">최종 금액<strong class="sound_only"> 필수</strong></label></th>
                            <td id="total_amount"><?php echo number_format($refundable_amount) ?>원</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            

            <div class="btn_win02 btn_win">
                <button type="submit" class="btn_submit btn">환불</button>
                <button type="button" class="btn_02 btn" onclick="window.close();">창닫기</button>
            </div>
        </div>
    </form>

</div>

<script>
    $(function() {
        $("input[name=cancel_amount]").on('keyup', function(){
            let max = $(this).data("max");
            let input = $(this).val();

            if (max < input) {
                $(this).val(max);
            }

            let total_amount = max - $(this).val();

            $("#total_amount").html(new Intl.NumberFormat().format(total_amount) + "원");
        });
    });

    function check_cancel_form(form)
    {
        if (!form.cancel_reason.value) {
            alert("환불사유를 입력해주세요.");
            return false;
        }
        if (!form.cancel_amount.value) {
            alert("환불금액을 입력해주세요.");
            return false;
        }
        if (confirm("환불을 진행하시겠습니까?")) {
            return true;
        } else {
            return false;
        }
    }

    function input_cancel_amount()
    {
        let amount = '<?php echo $refund_amount ?>';
        $("input[name=cancel_amount]").val(amount);
    }
</script>

<?php
require_once G5_PATH . '/tail.sub.php';
