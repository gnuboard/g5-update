<?php
$sub_menu = '800940';
include_once './_common.php';

$g5['title'] = '결제정보';
require_once G5_PATH . '/head.sub.php';

auth_check_menu($auth, $sub_menu, "w");

/* 변수 선언 */
$id = isset($_GET['id']) ? preg_replace('/[^0-9]/', '', $_GET['id']) : 0;

$billing        = new Billing($billing_conf['bc_pg_code']);
$history_model  = new BillingHistoryModel();
$cancel_model   = new BillingCancelModel();

$history        = $history_model->selectOneById($id);
$cancel_amount  = $cancel_model->selectTotalCancelAmount($history['payment_no']);
$cancel_list    = $cancel_model->selectList($history['payment_no']);

$refundable_amount      = (int)$history['amount'] - (int)$cancel_amount;
$display_billing_key    = $billing->displayBillKey($history['billing_key']);

?>

<div id="menu_frm" class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <form name="cancel_form" id="cancel_form" class="new_win_con">
        <div class="new_win_desc">
            <h3>결제 정보</h3>
        </div>
        <div id="menu_result">
            <div class="tbl_frm01 tbl_wrap">
                <table>
                    <colgroup>
                        <col class="grid_2" style="width:20%;">
                        <col class="grid_2" style="width:30%;">
                        <col class="grid_2" style="width:20%;">
                        <col class="grid_2" style="width:30%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <th scope="row">결제 번호</th>
                            <td colspan="3"><?php echo $history['payment_no'] ?></td>
                        </tr>
                        <tr>
                            <th scope="row">결제 금액</th>
                            <td><?php echo number_format($history['amount']) ?>원</td>
                            <th scope="row">결제상태</th>
                            <td><?php echo "{$history['result_message']} ({$history['result_code']})" ?></td>
                        </tr>
                        <tr>
                            <th scope="row">결제 빌링키</th>
                            <td><?php echo $display_billing_key ?></td>
                            <th scope="row">카드 이름</th>
                            <td><?php echo $history['card_name'] ?></td>
                        </tr>
                        <tr>
                            <th scope="row">결제일</th>
                            <td><?php echo $history['payment_date'] ?></td>
                            <th scope="row">만료일</th>
                            <td><?php echo $history['expiration_date'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($cancel_list) > 0) { ?>
        <div class="new_win_desc">
            <h3>환불 정보</h3>
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
                            <th scope="row">결제 원 금액</th>
                            <td style="color:gray; font-weight:bold;"><?php echo number_format($history['amount']) ?>원</td>
                        </tr>
                            <th scope="row">환불 금액</th>
                            <td style="color:red; font-weight:bold;">-<?php echo number_format($cancel_amount) ?>원</td>
                        </tr>
                        <tr>
                            <th scope="row">남은 금액</th>
                            <td style="color:green; font-weight:bold;"><?php echo number_format($refundable_amount) ?>원</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="new_win_desc">
            <h3>환불 내역</h3>
        </div>
        <div id="menu_result">
            <?php foreach ($cancel_list as $cancel) { ?>
            <div class="tbl_frm01 tbl_wrap">
                <table>
                    <colgroup>
                        <col class="grid_2" style="width:20%;">
                        <col class="grid_2" style="width:30%;">
                        <col class="grid_2" style="width:20%;">
                        <col class="grid_2" style="width:30%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <th scope="row">금액</th>
                            <td><?php echo number_format($cancel['cancel_amount']) ?>원</td>
                            <th scope="row">일시</th>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($cancel['cancel_time'])) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">환불사유</th>
                            <td colspan="3"><?php echo $cancel['cancel_reason'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
        <div class="btn_win02 btn_win">
            <button type="button" class="btn_02 btn" onclick="window.close();">창닫기</button>
        </div>
    </form>
</div>
<?php
require_once G5_PATH . '/tail.sub.php';
