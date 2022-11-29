<?php
$sub_menu = '400920';
include_once './_common.php';
require_once G5_LIB_PATH . '/billing/kcp/config.php';
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

auth_check_menu($auth, $sub_menu, "w");
check_admin_token();

$information_model  = new BillingInformationModel();

$status = isset($_POST['status']) ? preg_replace('/[^0-9]/', '', $_POST['status']) : 0;
$od_id  = isset($_POST['od_id']) ? safe_replace_regex($_POST['od_id'], 'od_id') : '';

$information_model->updateStatus($od_id, $status);

goto_url("./batch_form.php?w=u&amp;od_id={$od_id}");