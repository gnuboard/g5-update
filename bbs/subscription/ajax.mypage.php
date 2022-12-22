<?php

require_once(dirname(__FILE__) . '/_common.php');
require_once G5_BBS_PATH . '/subscription/subscription_service.php';

header('Content-type: application/json; charset=utf-8');
/**
 *  마이페이지 ajax API , 컨트롤러.
 */

$input_data = json_decode(file_get_contents('php://input'), true);
$work_mode = isset($input_data['w']) ? $input_data['w'] : '';
if (empty($work_mode)) {
    response_json('잘못된 요청입니다.', 400);
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($work_mode) {
        case  'all' : {
            //그누보드 페이징은 1 부터시작.
            $page = isset($input_data['page']) ? $input_data['page'] : 1;
            $rows = isset($input_data['rows']) ? $input_data['rows'] : 10;
            $offset = ($page - 1) * $rows; // 시작 열을 구함
            $query_data = array(
                'status' => 1,
                'offset' => $offset,
                'rows' => $rows
            );

            $result = get_myservice($query_data);
            if ($result === false) {
                response_json('잘못된 요청입니다.', 400);
            } else {
                response_json($result);
            }
        }
        break;
        case 'id' : {
            if(!isset($input_data['od_id']) || empty($input_data['od_id'])){
                response_json('요청파라미터가 잘못되었습니다', 400);
            }
            get_myservice_info($input_data['od_id']);
        }
            break;

    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($work_mode) {
        case 'cancel' : {
            if(!isset($input_data['od_id']) || empty($input_data['od_id'])){
                response_json('요청파라미터가 잘못되었습니다', 400);
            }

            $result = cancel_myservice($input_data['od_id']);
            if ($result === false) {
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

