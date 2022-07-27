<?php
include_once "./_common.php";

$data = array();
$content = "";

try {
    $version = isset($_POST['version']) ? $_POST['version'] : null;
    if ($version == null) {
        throw new Exception("버전을 입력해주세요");
    }

    $result = $g5['update']->getVersionModifyContent($version);

    preg_match_all('/(?:(?:https?|ftp):)?\/\/[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i', $result, $match);

    $content_url = $match[0];
    foreach ($content_url as $key => $var) {
        $result = str_replace($var, "@" . $key . "@", $result);
    }
    foreach ($content_url as $key => $var) {
        $result = str_replace('@' . $key . '@', '<a class="a_style" href="' . $var . '" target="_blank">변경코드확인</a>', $result);
    }
    $content .= $g5['update']->setHtmlspecialcharsDecode($result);

    $data['error'] = 0;
    $data['content'] = $content;
} catch (Exception $e) {
    $data['error'] = 1;
    $data['code'] = $e->getCode();
    $data['message'] = $e->getMessage();
}

die(json_encode($data));
