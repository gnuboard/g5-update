<?php
require_once '_common.php';

// $_SERVER['HTTP_REFERER']

$uuid           = $_POST['uuid'];
$device_name    = $_POST['device_name'];

if (is_null($uuid) || is_null($device_name)) {
    echo json_encode(array('result' => false, 'message' => '실패11'));
    exit;
}

$sql = "INSERT INTO {$g5['member_table']}_device SET
        id = '{$uuid}',
        mb_id = '{$member['mb_id']}',
        device_name = '{$device_name}',
        date = NOW()
        ";
$query_result = sql_query($sql);

if ($query_result) {
    $result['result'] = true;
    $result['message'] = '성공';
} else {
    $result['result'] = false;
    $result['message'] = '실패';
}

echo json_encode($result);
