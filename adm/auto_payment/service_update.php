<?php
$sub_menu = '400920';
include_once './_common.php';

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';
auth_check_menu($auth, $sub_menu, "w");
check_admin_token();

@mkdir(G5_DATA_PATH."/batch", G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH."/batch", G5_DIR_PERMISSION);

/** @todo add dbconfig.php */
$g5['batch_service_table']          = G5_TABLE_PREFIX . 'batch_service'; 
$g5['batch_service_price_table']    = G5_TABLE_PREFIX . 'batch_service_price';
$g5['batch_service_date_table']     = G5_TABLE_PREFIX . 'batch_service_date';

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
    $price_array[$key]['price_id']    = isset($_POST['price_id'][$key]) ? preg_replace('/[^0-9]/', '', $_POST['price_id'][$key]) : null;
    $price_array[$key]['price']       = isset($price) ? preg_replace('/[^0-9]/', '', $price) : 0;
    $price_array[$key]['apply_date']  = isset($_POST['price_apply_date'][$key]) ? clean_xss_tags($_POST['price_apply_date'][$key], 1, 1) : '';
}
sort($price_array);

/* 결제주기 */
$date_array = array();
foreach($_POST['recurring_count'] as $key => $count) {
    $date_array[$key]['date_id']          = isset($_POST['date_id'][$key]) ? preg_replace('/[^0-9]/', '', $_POST['date_id'][$key]) : null;
    $date_array[$key]['recurring_count']  = isset($count) ? preg_replace('/[^0-9]/', '',  $count) : 0;
    $date_array[$key]['recurring_unit']   = isset($_POST['recurring_unit'][$key]) ? clean_xss_tags($_POST['recurring_unit'][$key], 1, 1) : '';
    $date_array[$key]['apply_date']       = isset($_POST['recurring_apply_date'][$key]) ? clean_xss_tags($_POST['recurring_apply_date'][$key], 1, 1) : '';
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

$sql_common = " bo_table            = '{$bo_table}',
                service_name        = '{$service_name}',
                service_summary     = '{$service_summary}',
                service_order       = '{$service_order}',
                service_use         = '{$service_use}',
                service_url         = '{$service_url}',
                service_hook        = '{$service_hook}',
                service_explan          = '{$service_explan}',
                service_mobile_explan   = '{$service_mobile_explan}',
                service_expiration      = '{$service_expiration}',
                service_expiration_unit = '{$service_expiration_unit}'";
if ($w == "") {
    $sql = "INSERT INTO {$g5['batch_service_table']} SET
                {$sql_common}";
    sql_query($sql);

    $service_id = sql_insert_id();

    foreach ($price_array as $arr) {
        $sql_price = "INSERT INTO {$g5['batch_service_price_table']} SET
                        service_id  = '{$service_id}',
                        price       = '{$arr['price']}',
                        apply_date  = '{$arr['apply_date']}'";
        sql_query($sql_price);
    }
    foreach ($date_array as $arr) {
        $sql_date = "INSERT INTO {$g5['batch_service_date_table']} SET
                        service_id      = '{$service_id}',
                        recurring_count = '{$arr['recurring_count']}',
                        recurring_unit  = '{$arr['recurring_unit']}',
                        apply_date      = '{$arr['apply_date']}'";
        sql_query($sql_date);
    }

} else if ($w == "u") {
    $sql = "UPDATE {$g5['batch_service_table']} SET
            {$sql_common}
            WHERE service_id = '{$service_id}'";
    sql_query($sql);

    // 가격,주기 업데이트
    if (count($_POST['price_id']) > 0) {
        $sql_delete_price = "DELETE FROM {$g5['batch_service_price_table']} WHERE service_id = '{$service_id}' AND price_id NOT IN (" . implode(",", $_POST['price_id']) . ")";
        sql_query($sql_delete_price);
        // echo $sql_delete_price . "<br>";
    }
    foreach ($price_array as $arr) {
        if (isset($arr['price_id'])) {
            $sql_price = "UPDATE {$g5['batch_service_price_table']} SET
                                price       = '{$arr['price']}',
                                apply_date  = '{$arr['apply_date']}'
                        WHERE price_id       = '{$arr['price_id']}'";
        } else {
            $sql_price = "INSERT INTO {$g5['batch_service_price_table']} SET
                        service_id  = '{$service_id}',
                        price       = '{$arr['price']}',
                        apply_date  = '{$arr['apply_date']}'";
        }
        // echo $sql_price . "<br>";
        sql_query($sql_price);
    }

    // delete
    if (count($_POST['date_id']) > 0) {
        $sql_delete_date = "DELETE FROM {$g5['batch_service_date_table']} WHERE service_id = '{$service_id}' AND date_id NOT IN (" . implode(",", $_POST['date_id']) . ")";
        sql_query($sql_delete_date);
        // echo $sql_delete_date . "<br>";
    }
    foreach ($date_array as $arr) {
        if (isset($arr['date_id'])) {
            $sql_date = "UPDATE {$g5['batch_service_date_table']} SET
                            recurring_count = '{$arr['recurring_count']}',
                            recurring_unit  = '{$arr['recurring_unit']}',
                            apply_date      = '{$arr['apply_date']}'
                        WHERE date_id       = '{$arr['date_id']}'";
        } else {
            $sql_date = "INSERT INTO {$g5['batch_service_date_table']} SET
                        service_id      = '{$service_id}',
                        recurring_count = '{$arr['recurring_count']}',
                        recurring_unit  = '{$arr['recurring_unit']}',
                        apply_date      = '{$arr['apply_date']}'";
        }
        // echo $sql_date . "<br>";
        sql_query($sql_date);
    }
}
if ($w == "" || $w == "u") {
    goto_url("./service_form.php?w=u&amp;service_id={$service_id}");
} else {
    goto_url("./service_list.php");
}
