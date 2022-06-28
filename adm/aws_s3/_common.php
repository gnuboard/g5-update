<?php
define('G5_IS_ADMIN', true);
include_once ('../../common.php');
include_once (G5_ADMIN_PATH . '/admin.lib.php');

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}
$g5['title'] = 's3 설정';