<?php

require_once(dirname(__FILE__) . '/_common.php');
require_once G5_BBS_PATH . '/subscription/subscription_service.php';

header('Content-type: application/json; charset=utf-8');
/**
 *  마이페이지 ajax API , 컨트롤러.
 */

$res_data = json_decode(file_get_contents('php://input'), true);
$work_mode = isset($res_data['w']) ? $res_data['w'] : '';
if(empty($work_mode)){
    response_json('잘못된 요청입니다.', 400);
}

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

