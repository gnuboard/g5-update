<?php
$sub_menu = '400920';
include_once './_common.php';
require_once G5_PATH . '/bbs/kcp-batch/G5Mysqli.php';
require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, "w");
check_admin_token();

@mkdir(G5_DATA_PATH . '/batch', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH . '/batch', G5_DIR_PERMISSION);

$g5Mysqli = new G5Mysqli();

/** Form Data */
/* 기본정보 */
$service_id         = isset($_POST['service_id']) ? preg_replace('/[^0-9]/', '', $_POST['service_id']) : 0;
$bo_table           = isset($_POST['bo_table']) ? clean_xss_tags($_POST['bo_table'], 1, 1) : '';
$service_name       = isset($_POST['service_name']) ? clean_xss_tags($_POST['service_name'], 1, 1) : '';
$service_summary    = isset($_POST['service_summary']) ? clean_xss_tags($_POST['service_summary'], 1, 1) : '';
$service_order      = isset($_POST['service_order']) ? preg_replace('/[^0-9]/', '', $_POST['service_order']) : 0;
$service_use        = isset($_POST['service_use']) ? preg_replace('/[^0-9]/', '', $_POST['service_use']) : 0;
$service_url        = isset($_POST['service_url']) ? strip_tags(clean_xss_attributes($_POST['service_url'])) : '';
$service_hook       = isset($_POST['service_hook']) ? clean_xss_tags($_POST['service_hook'], 1, 1) : '';
$service_explan             = isset($_POST['service_explan']) ? $_POST['service_explan'] : '';
$service_mobile_explan      = isset($_POST['service_mobile_explan']) ? $_POST['service_mobile_explan'] : '';
$service_expiration         = isset($_POST['service_expiration']) ? clean_xss_tags($_POST['service_expiration'], 1, 1) : '';
$service_expiration_unit    = isset($_POST['service_expiration_unit']) ? clean_xss_tags($_POST['service_expiration_unit'], 1, 1) : '';

/* 가격 */
$price_array = array();
foreach($_POST['price'] as $key => $price) {
    if (!empty($price)) {
        $price_array[$key]['price_id']    = isset($_POST['price_id'][$key]) ? preg_replace('/[^0-9]/', '', $_POST['price_id'][$key]) : null;
        $price_array[$key]['price']       = isset($price) ? preg_replace('/[^0-9]/', '', $price) : 0;
        $price_array[$key]['apply_date']  = isset($_POST['price_apply_date'][$key]) ? clean_xss_tags($_POST['price_apply_date'][$key], 1, 1) : '';
    }
}
sort($price_array);

/* 결제주기 */
$date_array = array();
foreach($_POST['recurring_count'] as $key => $count) {
    if (!empty($count)) {
        $date_array[$key]['date_id']          = isset($_POST['date_id'][$key]) ? preg_replace('/[^0-9]/', '', $_POST['date_id'][$key]) : null;
        $date_array[$key]['recurring_count']  = isset($count) ? preg_replace('/[^0-9]/', '',  $count) : 0;
        $date_array[$key]['recurring_unit']   = isset($_POST['recurring_unit'][$key]) ? clean_xss_tags($_POST['recurring_unit'][$key], 1, 1) : '';
        $date_array[$key]['apply_date']       = isset($_POST['recurring_apply_date'][$key]) ? clean_xss_tags($_POST['recurring_apply_date'][$key], 1, 1) : '';
    }
}
/* 이미지 (추가예정) */ 
// $bn_bimg_name = isset($_FILES['bn_bimg']['name']) ? $_FILES['bn_bimg']['name'] : '';
// if ($bn_bimg_del)  @unlink(G5_DATA_PATH."/banner/$bn_id");

//파일이 이미지인지 체크합니다.
// if( $bn_bimg || $bn_bimg_name ){
//     if( !preg_match('/\.(gif|jpe?g|bmp|png)$/i', $bn_bimg_name) ){
//         alert("이미지 파일만 업로드 할수 있습니다.");
//     }
//     $timg = @getimagesize($bn_bimg);
//     if ($timg['2'] < 1 || $timg['2'] > 16){
//         alert("이미지 파일만 업로드 할수 있습니다.");
//     }
// }

$bind_param = array();
array_push($bind_param, $bo_table, $service_name, $service_summary, $service_order, $service_use,
$service_url, $service_hook, $service_explan, $service_mobile_explan, $service_expiration, $service_expiration_unit);
$sql_common = " bo_table            = ?,
                service_name        = ?,
                service_summary     = ?,
                service_order       = ?,
                service_use         = ?,
                service_url         = ?,
                service_hook        = ?,
                service_explan          = ?,
                service_mobile_explan   = ?,
                service_expiration      = ?,
                service_expiration_unit = ?";
if ($w == "") {
    $sql = "INSERT INTO {$g5['batch_service_table']} SET
                {$sql_common}";
    $g5Mysqli->execSQL($sql, $bind_param, true);

    $service_id = $g5Mysqli->insertId();

    foreach ($price_array as $arr) {
        $sql_price = "INSERT INTO {$g5['batch_service_price_table']} SET
                        service_id  = ?,
                        price       = ?,
                        apply_date  = ?";
        $g5Mysqli->execSQL($sql_price, array($service_id, $arr['price'], $arr['apply_date']), true);
    }
    foreach ($date_array as $arr) {
        $sql_date = "INSERT INTO {$g5['batch_service_date_table']} SET
                        service_id      = ?,
                        recurring_count = ?,
                        recurring_unit  = ?,
                        apply_date      = ?";
        $g5Mysqli->execSQL($sql_date, array($service_id, $arr['recurring_count'], $arr['recurring_unit'], $arr['apply_date']), true);
    }

} else if ($w == "u") {
    $sql = "UPDATE {$g5['batch_service_table']} SET
                {$sql_common}
            WHERE service_id = ?";
    array_push($bind_param, $service_id);
    $g5Mysqli->execSQL($sql, $bind_param, true);
    
    /* 가격 업데이트 */
    // delete
    $price_count = count($price_array);
    if ($price_count > 0) {
        $in         = '';
        $bind_price = array($service_id);

        foreach($price_array as $price) {
            $in .= ($in == '') ? '?' : ',?';
            array_push($bind_price, $price['price_id']);
        }
        $sql_delete_price = "DELETE FROM {$g5['batch_service_price_table']} WHERE service_id = ? AND price_id NOT IN ({$in})";
        $g5Mysqli->execSQL($sql_delete_price, $bind_price, true);
    }
    // insert or update
    foreach ($price_array as $arr) {
        $bind_price = array();
        if (!empty($arr['price_id'])) {
            $sql_price = "UPDATE {$g5['batch_service_price_table']} SET
                                price       = ?,
                                apply_date  = ?
                        WHERE price_id      = ?";
            array_push($bind_price, $arr['price'], $arr['apply_date'], $arr['price_id']);
        } else {
            $sql_price = "INSERT INTO {$g5['batch_service_price_table']} SET
                        service_id  = ?,
                        price       = ?,
                        apply_date  = ?";
            array_push($bind_price, $service_id, $arr['price'], $arr['apply_date']);
        }
        $g5Mysqli->execSQL($sql_price, $bind_price, true);
    }
    /* 주기 업데이트 */
    // delete
    $date_count = count($date_array);
    if ($date_count > 0) {
        $in         = '';
        $bind_date = array($service_id);

        foreach($date_array as $date) {
            $in .= ($in == '') ? '?' : ',?';
            array_push($bind_date, $date['date_id']);
        }
        $sql_delete_date = "DELETE FROM {$g5['batch_service_date_table']} WHERE service_id = ? AND date_id NOT IN ({$in})";
        $g5Mysqli->execSQL($sql_delete_date, $bind_date, true);
    }
    // insert or update
    foreach ($date_array as $arr) {
        $bind_date = array();
        if (!empty($arr['date_id'])) {
            $sql_date = "UPDATE {$g5['batch_service_date_table']} SET
                            recurring_count = ?,
                            recurring_unit  = ?,
                            apply_date      = ?
                        WHERE date_id       = ?";
            array_push($bind_date, $arr['recurring_count'], $arr['recurring_unit'], $arr['apply_date'], $arr['date_id']);
        } else {
            $sql_date = "INSERT INTO {$g5['batch_service_date_table']} SET
                        service_id      = ?,
                        recurring_count = ?,
                        recurring_unit  = ?,
                        apply_date      = ?";
            array_push($bind_date, $service_id, $arr['recurring_count'], $arr['recurring_unit'], $arr['apply_date']);
        }
        $g5Mysqli->execSQL($sql_date, $bind_date, true);
    }
}
if ($w == "" || $w == "u") {
    goto_url("./service_form.php?w=u&amp;service_id={$service_id}");
} else {
    goto_url("./service_list.php");
}
