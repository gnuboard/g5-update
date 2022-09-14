<?php
include_once './_common.php';
include_once '../settle_toss.inc.php';

$code       = isset($_GET['code']) ? clean_xss_tags($_GET['code']) : '';
$message    = isset($_GET['message']) ? clean_xss_tags($_GET['message']) : '';
$od_id      = isset($_GET['orderId']) ? clean_xss_tags($_GET['orderId']) : '';
$return_url = G5_SHOP_URL;

if ($od_id) {
    $sql = "SELECT ct_direct FROM {$g5['g5_shop_cart_table']} WHERE od_id = (SELECT cart_id FROM {$g5['g5_shop_order_data_table']} WHERE od_id = '{$od_id}' limit 1)";
    $cart = sql_fetch($sql);
    $return_url .= "/orderform.php" . ($cart['ct_direct'] == '1' ? '?sw_direct=1' : '');
}

alert("[{$code}] " . $message, $return_url);
