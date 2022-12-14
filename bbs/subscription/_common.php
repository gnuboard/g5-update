<?php

require_once(dirname(__FILE__) . '/../../common.php');

function han($s)
{
    $result = json_decode('{"s":"' . $s . '"}');
    return reset($result);
}

/**
 * PHP 5.3 이하에서 json_encode JSON_UNESCAPED_UNICODE 구현
 * @param $str
 * @return array|string|string[]|null
 */
function to_han($str)
{
    return preg_replace('/(\\\u[a-f0-9]+)+/e', 'han("$0")', $str);
}

/**
 * json 형식으로 메시지를 출력 후 exit 합니다.
 * @param string $msg
 * @param string $http_status_code
 * @return void
 */
function response_json($msg, $http_status_code = 200)
{
    $res_data = array('result_msg' => $msg);
    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($res_data, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($res_data));
    }

    header('Content-type: application/json; charset=utf-8', true, $http_status_code);
    exit;
}