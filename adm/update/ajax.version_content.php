<?php
include_once "./_common.php";

$data = array();
$title = "";
$content = "";

try {
    $version = isset($_POST['version']) ? $_POST['version'] : null;
    if ($version == null) {
        throw new Exception("버전을 입력해주세요");
    }

    $result = $g5['update']->getVersionModifyContent($version);
    if ($result == false) {
        throw new Exception("정보를 찾을 수 없습니다.");
    }

    preg_match_all('/(?:(?:https?|ftp):)?\/\/[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i', $result, $match);

    $content_url = $match[0];
    foreach ($content_url as $key => $var) {
        $result = str_replace($var, "@" . $key . "@", $result);
    }

    $title .= "<p>" . $version . " 버전 수정</p>";
    $content .= "<p>";
    foreach ($content_url as $key => $var) {
        $result = str_replace('@' . $key . '@', '<a class="a_style" href="' . $var . '" target="_blank">변경코드확인</a>', $result);
    }
    $content .= htmlspecialchars_decode($result, ENT_HTML5);
    $content .= "</p><br>";

    $data['error']      = 0;
    $data['title']      = $title;
    $data['item']       = $content;
} catch (Exception $e) {
    $data['code']    = $e->getCode();
    $data['message'] = $e->getMessage();
}

die(json_encode($data));
