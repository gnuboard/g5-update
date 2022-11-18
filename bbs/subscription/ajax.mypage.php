<?php

require_once '../../common.php';
require_once 'mypage.php';

header('Content-type: application/json; charset=utf-8');
/**
 *  마이페이지 ajax API , 컨트롤러.
 */

$resData = json_decode(file_get_contents('php://input'), true);
$workingMode = $resData['w'];
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($workingMode) {
        case  'all' : {
            $result =  showMyServiceList();
            if($result === false){
                responseJson('잘못된 요청입니다.', 400);
            } else {
                if (PHP_VERSION_ID >= 50400) {
                    echo json_encode($resData, JSON_UNESCAPED_UNICODE);
                } else {
                    echo to_han(json_encode($resData));
                }
            }
        }
        break;
        case 'id' : {
            echo '';
        }
            break;

    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    switch ($workingMode) {
        case 'service_cancel' : {
            $od_id = $resData['od_id'];
            $result = cancelMyService($od_id);
            if($result === false){
                responseJson('잘못된 요청입니다.', 400);
            } else {
                echo json_encode($result);
            }
        }
        break;

        default : {
            responseJson('요청파라미터가 잘못되었습니다', 400);
        }
        break;

    }
}

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
 * @param string $httpStateNo
 * @return void
 */
function responseJson($msg, $httpStateNo = 200)
{
    $resData = array('msg' => $msg);
    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($resData, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($resData));
    }

    header('Content-type: application/json; charset=utf-8', true, $httpStateNo);
    exit;
}

