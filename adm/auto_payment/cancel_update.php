<?php
$pg_code = 'kcp';
$sub_menu = '800940';
include_once './_common.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

// auth_check_menu($auth, $sub_menu, "w");

$billing        = new Billing($pg_code);
$history_model  = new BillingHistoryModel();
$cancel_model   = new BillingCancelModel();

$cancel_reason  = isset($_POST['cancel_reason']) ? clean_xss_tags($_POST['cancel_reason']) : '';
$cancel_amount  = isset($_POST['cancel_amount']) ? preg_replace('/[^0-9]/', '', $_POST['cancel_amount']) : 0;
$payment_no     = isset($_POST['payment_no']) ? preg_replace('/[^0-9]/', '', $_POST['payment_no']) : 0;
$od_id          = isset($_POST['od_id']) ? clean_xss_tags($_POST['od_id']) : '';
$id             = isset($_POST['id']) ? preg_replace('/[^0-9]/', '', $_POST['id']) : 0;

$history                = $history_model->selectOneById($id);
$total_cancel_amount    = $cancel_model->selectTotalCancelAmount($od_id);
$refundable_amount      = (int)$history['amount'] - (int)$total_cancel_amount;

if ($refundable_amount == $cancel_amount) {
    $cancel_res = $billing->pg->requestCancelBilling($payment_no, $cancel_reason);
    $cancel_res['type'] = 'all';
} else {
    $cancel_res = $billing->pg->requestPartialCancelBilling($payment_no, $cancel_reason, $cancel_amount, $refundable_amount);
    $cancel_res['type'] = 'partial';
}

$cancel_res = $billing->convertPgDataToCommonData($cancel_res);

// 취소이력 저장
$cancel_res['od_id']            = $od_id;
$cancel_res['payment_no']       = $payment_no;
$cancel_res['cancel_reason']    = $cancel_reason;
$cancel_res['cancel_amount']    = $cancel_amount;
$cancel_model->insert($cancel_res);

echo '<script>
alert("'.$cancel_res['result_message'].'");
window.close(); 
opener.document.location.reload();
</script>';