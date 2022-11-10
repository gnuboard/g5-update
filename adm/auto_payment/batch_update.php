<?php
$sub_menu = '400920';
include_once './_common.php';

auth_check_menu($auth, $sub_menu, "w");
check_admin_token();

/** @todo add dbconfig.php */
$g5['batch_info_table']             = G5_TABLE_PREFIX . 'batch_info';
$g5['batch_payment_table']          = G5_TABLE_PREFIX . 'batch_payment';
$g5['batch_service_table']          = G5_TABLE_PREFIX . 'batch_service';
$g5['batch_service_price_table']    = G5_TABLE_PREFIX . 'batch_service_price';
$g5['batch_service_date_table']     = G5_TABLE_PREFIX . 'batch_service_date';

$status = isset($_POST['status']) ? preg_replace('/[^0-9]/', '', $_POST['status']) : 0;

$sql = "UPDATE {$g5['batch_info_table']} SET
            status = {$status}
        WHERE od_id = '{$od_id}'";
sql_query($sql);

goto_url("./batch_form.php?w=u&amp;od_id={$od_id}");