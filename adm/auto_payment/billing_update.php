<?php
$sub_menu = '400930';
$pg_code = 'kcp';
include_once './_common.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$information_model  = new BillingInformationModel();

$od_id  = isset($_REQUEST['od_id']) ? safe_replace_regex($_REQUEST['od_id'], 'od_id') : '';
$data   = array(
    'status'            => isset($_POST['status']) ? preg_replace('/[^0-9]/', '', $_POST['status']) : 0,
    'next_payment_date' => isset($_POST['next_payment_date']) ? $_POST['next_payment_date'] : null,
    'end_date'          => isset($_POST['end_date']) ? $_POST['end_date'] : null
);

$information_model->update($od_id, $data);

goto_url("./billing_form.php?w=u&amp;od_id={$od_id}");