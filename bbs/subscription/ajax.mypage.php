<?php

require_once '../../common.php';
require_once 'subscription/subscription_service';

header('Content-type: application/json; charset=utf-8');
/**
 *  마이페이지 ajax API , 컨트롤러.
 */

$res_data = json_decode(file_get_contents('php://input'), true);
$work_mode = $res_data['w'];
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($work_mode) {
        case  'all' : {
            $result =  get_myservice(1);//TODO 페이징
            if($result === false){
                response_json('잘못된 요청입니다.', 400);
            } else {
                if (PHP_VERSION_ID >= 50400) {
                    echo json_encode($res_data, JSON_UNESCAPED_UNICODE);
                } else {
                    echo to_han(json_encode($res_data));
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

    switch ($work_mode) {
        case 'cancel' : {
            $od_id = $res_data['od_id'];
            $result = cancel_myservice($od_id);
            if($result === false){
                response_json('잘못된 요청입니다.', 400);
            } else {
                echo json_encode($result);
            }
        }
        break;

        default : {
            response_json('요청파라미터가 잘못되었습니다', 400);
        }
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
 * @param string $http_status_code
 * @return void
 */
function response_json($msg, $http_status_code = 200)
{
    $res_data = array('msg' => $msg);
    if (PHP_VERSION_ID >= 50400) {
        echo json_encode($res_data, JSON_UNESCAPED_UNICODE);
    } else {
        echo to_han(json_encode($res_data));
    }

    header('Content-type: application/json; charset=utf-8', true, $http_status_code);
    exit;
}

