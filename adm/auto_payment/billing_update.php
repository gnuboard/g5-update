<?php
$sub_menu = '800940';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$information_model  = new BillingInformationModel();
$price_model        = new BillingServicePriceModel();

$od_id      = isset($_REQUEST['od_id']) ? safe_replace_regex($_REQUEST['od_id'], 'od_id') : '';
$end_date   = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$data       = array(
    'status'            => isset($_POST['status']) ? preg_replace('/[^0-9]/', '', $_POST['status']) : 0,
    'next_payment_date' => isset($_POST['next_payment_date']) ? $_POST['next_payment_date'] : null,
    'end_date'          => $end_date
);

// 결제종료일이 입력여부에 따라 price를 업데이트해준다.
$billing_info = $information_model->selectOneByOrderId($od_id);
if ($end_date != null) {
    $data['price'] = (int)$price_model->selectCurrentPrice($billing_info['service_id']);
} else {
    $data['price'] = 0;
}

$information_model->update($od_id, $data);

goto_url("./billing_form.php?w=u&amp;od_id={$od_id}");