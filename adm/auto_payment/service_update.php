<?php
$sub_menu = '800930';
$pg_code = 'kcp';
include_once './_common.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

@mkdir(G5_DATA_PATH . '/billing', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH . '/billing', G5_DIR_PERMISSION);

$service_model  = new BillingServiceModel();
$price_model    = new BillingServicePriceModel();

/** Form Data */
/* 기본정보 */
$service_id = isset($_POST['service_id']) ? preg_replace('/[^0-9]/', '', $_POST['service_id']) : 0;
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
    "base_price"        => isset($_POST['base_price']) ? preg_replace('/[^0-9]/', '', $_POST['base_price']) : 0
);

/* 가격 */
$price_array = array();
if (isset($_POST['price'])) {
    foreach($_POST['price'] as $key => $price) {
        if (!empty($price)) {
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

if ($w == "" || $w == "u") {
    goto_url("./service_form.php?w=u&amp;service_id={$service_id}");
} else {
    goto_url("./service_list.php");
}
