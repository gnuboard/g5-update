<?php
include_once './_common.php';
include_once '../settle_toss.inc.php';

// https://{ORIGIN}/fail?code={ERROR_CODE}&message={ERROR_MESSAGE}&orderId={ORDER_ID}
$code = $_GET['code'];
$message = $_GET['message'];
$od_id = $_GET['orderId'];

alert("[{$code}] " . $message);
