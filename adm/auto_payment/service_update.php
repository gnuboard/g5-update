<?php
$sub_menu = '800930';
include_once './_common.php';

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$default_path = G5_DATA_PATH . '/billing';
@mkdir($default_path, G5_DIR_PERMISSION);
@chmod($default_path, G5_DIR_PERMISSION);

$service_model  = new BillingServiceModel();
$price_model    = new BillingServicePriceModel();

/** Form Data */
/* 기본정보 */
$service_id = isset($_POST['service_id']) ? preg_replace('/[^0-9]/', '', $_POST['service_id']) : 0;
$base_price = isset($_POST['base_price']) ? preg_replace('/[^0-9]/', '', $_POST['base_price']) : 0;
$data = array(
    "name"              => isset($_POST['name']) ? clean_xss_tags($_POST['name'], 1, 1) : '',
    "summary"           => isset($_POST['summary']) ? clean_xss_tags($_POST['summary'], 1, 1) : '',
    "order"             => isset($_POST['order']) ? preg_replace('/[^0-9]/', '', $_POST['order']) : 0,
    "is_use"            => isset($_POST['is_use']) ? preg_replace('/[^0-9]/', '', $_POST['is_use']) : 0,
    "explan"            => isset($_POST['explan']) ? $_POST['explan'] : '',
    "mobile_explan"     => isset($_POST['mobile_explan']) ? $_POST['mobile_explan'] : '',
    "image_path"        => isset($_POST['image_path']) ? clean_xss_tags($_POST['image_path']) : '',
    "expiration"        => isset($_POST['expiration']) ? clean_xss_tags($_POST['expiration'], 1, 1) : '',
    "expiration_unit"   => isset($_POST['expiration_unit']) ? clean_xss_tags($_POST['expiration_unit'], 1, 1) : '',
    "recurring"         => isset($_POST['recurring']) ? preg_replace('/[^0-9]/', '', $_POST['recurring']) : 0,
    "recurring_unit"    => isset($_POST['recurring_unit']) ? clean_xss_tags($_POST['recurring_unit'], 1, 1) : '',
    "service_table"     => isset($_POST['service_table']) ? clean_xss_tags($_POST['service_table'], 1, 1) : '',
    "service_url"       => isset($_POST['service_url']) ? strip_tags(clean_xss_attributes($_POST['service_url'])) : '',
    "service_hook_code" => isset($_POST['service_hook_code']) ? clean_xss_tags($_POST['service_hook_code'], 1, 1) : '',
    "base_price"        => $base_price
);

/* 가격 */
$price_array = array();
if (isset($_POST['price'])) {
    foreach($_POST['price'] as $key => $price) {
        if (isset($price)) {
            $price_array[$key]['id']                = isset($_POST['id'][$key]) ? preg_replace('/[^0-9]/', '', $_POST['id'][$key]) : null;
            $price_array[$key]['price']             = isset($price) ? preg_replace('/[^0-9]/', '', $price) : 0;
            $price_array[$key]['application_date']      = !empty($_POST['application_date'][$key]) ? clean_xss_tags($_POST['application_date'][$key], 1, 1) : null;
            $price_array[$key]['application_end_date']  = !empty($_POST['application_end_date'][$key]) ? clean_xss_tags($_POST['application_end_date'][$key], 1, 1) : null;
        }
    }
    sort($price_array);
}

if ($w == "") {
    $service_model->insert($data);

    $service_id = $service_model->g5Mysqli->insertId();

    foreach ($price_array as $price) {
        $price_data = array(
            "service_id"    => $service_id,
            "price"         => $price['price'],
            "application_date" => $price['application_date'],
            "application_end_date" => $price['application_end_date']
        );
        $price_model->insert($price_data);
    }
} else if ($w == "u") {
    $service_model->update($service_id, $data);

    /* 가격 업데이트 */
    // delete
    $in         = '';
    $bind_param = array($service_id);
    foreach($price_array as $price) {
        if (isset($price['id'])) {
            $in .= ($in == '') ? '?' : ',?';
            array_push($bind_param, $price['id']);
        }
    }
    $sql = "DELETE FROM {$g5['billing_service_price_table']} WHERE service_id = ?";
    $sql .= ($in != '') ? " AND id NOT IN ({$in})" : '';
    $service_model->g5Mysqli->execSQL($sql, $bind_param, true);

    // insert or update
    foreach ($price_array as $price) {
        $data = array(
            "service_id"    => $service_id,
            "price"         => $price['price'],
            "application_date" => $price['application_date'],
            "application_end_date" => $price['application_end_date']
        );
        if (!empty($price['id'])) {
            $price_model->update($price['id'], $data);
        } else {
            $price_model->insert($data);
        }
    }
}

/* 가격변경 파일로그 추가 */
// 파일설명 변수 및 로그내용 선언
$log_path       = $default_path . "/log";
$file_name      = "service_price_change_log_" . $service_id . ".txt";
$log_file_path  = $log_path . "/" . $file_name;
$file_content = "====================================================";
$file_content .= "\n IP : " . $_SERVER['REMOTE_ADDR'];
$file_content .= "\n ID : " . $member['mb_id'];
$file_content .= "\n Date : " . date('Y-m-d H:i:s');
$file_content .= "\n Price : \n    " . number_format((int)$base_price);
foreach ($price_array as $price) {
    $id         = $price['id'] ? $price['id'] : 'New';
    $end_date   = $price['application_end_date'] ? $price['application_end_date'] : 'None';
    $log_price  = !empty($price['price']) ? number_format($price['price']) : 0; 
    $file_content .= "\n    " . $log_price . " / " .  $price['application_date'] . " / " . $end_date;
}
$file_content .= "\n====================================================\n";
// 경로 생성
if (!is_dir($log_path)) {
    @mkdir($log_path, G5_DIR_PERMISSION, true);
    @chmod($log_path, G5_DIR_PERMISSION);
}
// 이전파일 내용을 하단에 추가 (최신내용이 상단에 오게하기 위함)
$before_content = fopen($log_file_path, 'c+');
$before_filesize = filesize($log_file_path);
if ($before_filesize > 0) {
    $file_content .= (string)fread($before_content, filesize($log_file_path));
}
// 파일생성 및 쓰기
$resource = fopen($log_file_path, 'w+');
if ($resource) {
    fwrite($resource, $file_content);
    fclose($resource);
}

if ($w == "" || $w == "u") {
    goto_url("./service_form.php?w=u&amp;service_id={$service_id}");
} else {
    goto_url("./service_list.php");
}
