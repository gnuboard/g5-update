<?php

@set_time_limit(0);
$gmnow = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: 0'); // rfc2616 - Section 14.21
header('Last-Modified: ' . $gmnow);
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
@header('Content-Type: text/html; charset=utf-8');
@header('X-Robots-Tag: noindex');

$g5_path['path'] = '..';
include_once('../config.php');
include_once('./install.function.php');    // 인스톨 과정 함수 모음
include_once('./install.inc.php');
include_once('../lib/AwsSDK/aws-autoloader.php');
include_once('../lib/common.lib.php');
include_once('../lib/hook.lib.php');    // hook 함수 파일
include_once('../lib/get_data.lib.php');
include_once('../lib/uri.lib.php');    // URL 함수 파일
include_once('../lib/cache.lib.php');
include_once('./install.inc.php'); //설치되어있는지 체크
if (!defined('_GNUBOARD_')) {
    exit;
}

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

define('G5_S3CONFIG_FILE', 's3config.php');

$s3_use_check = isset($_POST['s3_use_check']) ? $_POST['s3_use_check'] : 'No';
$s3_access_key = isset($_POST['s3_access_key']) ? trim(safe_install_string_check($_POST['s3_access_key'])) : '';
$s3_secret_key = isset($_POST['s3_secret_key']) ? trim(safe_install_string_check($_POST['s3_secret_key'])) : '';
$s3_bucket_name = isset($_POST['s3_bucket_name']) ? trim(safe_install_string_check($_POST['s3_bucket_name'])) : '';
$s3_region = isset($_POST['s3_region']) ? trim(safe_install_string_check($_POST['s3_region'])) : '';
$is_g5_shop_install = isset($_POST['is_g5_shop_install']) ? $_POST['is_g5_shop_install'] : false;

if ($s3_use_check === 'No') {
    http_response_code(401);
    die();
}

try {
    $options = array(
        'key' => $s3_access_key,
        'secret' => $s3_secret_key
    );
    $s3_client = new S3Client(array(
        'credentials' => $options,
        'region' => $s3_region,
        'version' => 'latest'
    ));
    $result = $s3_client->listBuckets();
} catch (S3Exception $e) {
    $is_error = true;
    $response['error'] = true;
    $response['message'] = $e;
    die(json_encode($response));
}

$s3_client->registerStreamWrapper();

$dir_s3 = array(
    G5_DATA_DIR . '/editor',
    G5_DATA_DIR . '/file',
    G5_DATA_DIR . '/log',
    G5_DATA_DIR . '/member',
    G5_DATA_DIR . '/member_image',
    G5_DATA_DIR . '/content',
    G5_DATA_DIR . '/faq',
    G5_DATA_DIR . '/tmp'
);

$dir_count = count($dir_s3);
for ($i = 0; $i < $dir_count; $i++) {
    mkdir('s3://' . $s3_bucket_name . '/' . $dir_s3[$i], G5_DIR_PERMISSION);
}

$tmp_bo_table = array("notice", "qa", "free", "gallery");
$tmp_bo_table_count = count($tmp_bo_table);
for ($i = 0; $i < $tmp_bo_table_count; $i++) {
    $board_dir = G5_DATA_DIR . '/file/' . $tmp_bo_table[$i];
    mkdir('s3://' . $s3_bucket_name . '/' . $board_dir, G5_DIR_PERMISSION);
}
if ($is_g5_shop_install) {
    $shop_dir = array(
        G5_DATA_DIR . '/banner',
        G5_DATA_DIR . '/common',
        G5_DATA_DIR . '/event',
        G5_DATA_DIR . '/item'
    );
    $shop_dir_count = count($shop_dir);
    for ($i = 0; $i < $shop_dir_count; $i++) {
        mkdir('s3://' . $s3_bucket_name . '/' . $shop_dir[$i], G5_DIR_PERMISSION);
    }

    @copy('./logo_img', 's3://' . $s3_bucket_name . '/' . G5_DATA_DIR . '/common/logo_img');
    @copy('./logo_img', 's3://' . $s3_bucket_name . '/' . G5_DATA_DIR . '/common/logo_img2');
    @copy('./mobile_logo_img', 's3://' . $s3_bucket_name . '/' . G5_DATA_DIR . '/common/mobile_logo_img');
    @copy('./mobile_logo_img', 's3://' . $s3_bucket_name . '/' . G5_DATA_DIR . '/common/mobile_logo_img2');
}

// S3 설정 파일 생성

create_s3_config($s3_access_key, $s3_secret_key, $s3_bucket_name, $s3_region);

function create_s3_config($s3_access_key, $s3_secret_key, $s3_bucket_name, $s3_region)
{
    $file = '../' . G5_DATA_DIR . '/' . G5_S3CONFIG_FILE;
    $f = fopen($file, 'w');

    fwrite($f, "<?php\n");
    fwrite($f, "if (!defined('_GNUBOARD_')) exit;\n");
    fwrite($f, "define('G5_S3_ACCESS_KEY', '" . addcslashes($s3_access_key, "\\'") . "');\n");
    fwrite($f, "define('G5_S3_SECRET_KEY', '" . addcslashes($s3_secret_key, "\\'") . "');\n");
    fwrite($f, "define('G5_S3_BUCKET_NAME', '" . addcslashes($s3_bucket_name, "\\'") . "');\n");
    fwrite($f, "define('G5_S3_REGION', '" . addcslashes($s3_region, "\\'") . "');\n");

    fclose($f);
    @chmod($file, G5_FILE_PERMISSION);
}