<?php

/**
 * AWS S3 설정
 */

/**
 * G5_ADMIN_PATH
 * adm/view.php 에서 호출하기 떄문에 쓸 수있다.
 */
//require_once './_common.php';//G5_ADMIN_PATH . './_common.php';
require_once G5_ADMIN_PATH . '/_common.php';
echo get_include_path();
global $auth;

//$aws_s3_config = $this->config;
//
//if (isset($_POST['post_action'], $_POST['token'])) {
//    check_demo();
//    auth_check($auth[$this->admin_number], 'w');
//
//    $table_name = G5_TABLE_PREFIX . $this->db_table;
//
//    $tmp = sql_fetch("select * from $table_name limit 1");
//
//    $s3_bucket_name = (isset($_POST['s3_bucket_name']) && $_POST['s3_bucket_name']) ? strip_tags(
//        $_POST['s3_bucket_name']
//    ) : '';
//    $s3_user_key = (isset($_POST['s3_user_key']) && $_POST['s3_user_key']) ? strip_tags(
//        $_POST['s3_user_key']
//    ) : '';
//    $s3_user_secret = (isset($_POST['s3_user_secret']) && $_POST['s3_user_secret']) ? strip_tags(
//        $_POST['s3_user_secret']
//    ) : '';
//    $s3_access_control_list = (isset($_POST['s3_access_control_list']) && $_POST['s3_access_control_list']) ? strip_tags(
//        $_POST['s3_access_control_list']
//    ) : '';
//    $s3_save_mydata = (isset($_POST['s3_save_mydata']) && $_POST['s3_save_mydata']) ? (int)($_POST['s3_save_mydata']) : 0;
//    $s3_only_used_file = (isset($_POST['s3_only_used_file']) && $_POST['s3_only_used_file']) ? (int)($_POST['s3_only_used_file']) : 0;
//    $s3_bucket_region = (isset($_POST['s3_bucket_region']) && $_POST['s3_bucket_region']) ? strip_tags(
//        $_POST['s3_bucket_region']
//    ) : '';
//
//    $sql_common = "
//            set s3_bucket_name = '{$s3_bucket_name}',
//            s3_bucket_region = '{$s3_bucket_region}',
//            s3_user_key = '{$s3_user_key}',
//            s3_user_secret = '{$s3_user_secret}',
//            s3_access_control_list = '{$s3_access_control_list}',
//            s3_save_mydata = '{$s3_save_mydata}',
//            s3_only_used_file = '{$s3_only_used_file}'
//        ";
//
//    if ($tmp) {
//        $sql = " update $table_name $sql_common ";
//    } else {
//        $sql = " insert into $table_name $sql_common ";
//    }
//
//    if (sql_query($sql, false)) {
//        $aws_s3_config = $this->get_config(false);
//    }
//}
//
//$all_regions = $this->get_regions();
//
//echo "vvvvvvvvvvv";