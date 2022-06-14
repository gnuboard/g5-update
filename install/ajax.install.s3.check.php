<?php

header('Content-Type: text/html; charset=UTF-8');

//AWS SDK 는 PHP 5.5 이상 지원.
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\Credentials;

include_once '../lib/AwsSDK/aws-autoloader.php';
$g5_path['path'] = '..';
include_once('../config.php');
include_once('./install.function.php');    // 인스톨 과정 함수 모음
include_once('../lib/common.lib.php');    // 공통 라이브러리
include_once('../lib/hook.lib.php');    // hook 함수 파일
include_once('../lib/get_data.lib.php');    // 데이터 가져오는 함수 모음

$data_path = '../' . G5_DATA_DIR;
$db_config_file = $data_path . '/' . G5_DBCONFIG_FILE;
if (file_exists($db_config_file)) {
    die(install_json_msg('프로그램이 이미 설치되어 있습니다.'));
}

$check_mode = isset($_POST['check_mode']) ? $_POST['check_mode'] : null;

if ($check_mode === null) {
    die;
}

if ($check_mode === 's3_connect_check') {
    $region = isset($_POST['region']) ? $_POST['region'] : '';
    $name = isset($_POST['bucket_name']) ? $_POST['bucket_name'] : '';
    $access_key = isset($_POST['access_key']) ? $_POST['access_key'] : '';
    $secret_key = isset($_POST['secret_key']) ? $_POST['secret_key'] : '';

    $result = s3ConnectCheck($region, $name, $access_key, $secret_key);

    $result['error'] === true ? http_response_code(400) : http_response_code(202); //http 상태코드
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * S3 연결 체크
 * @param string $region
 * @param string $bucket_name
 * @param string $access_key
 * @param string $secret_key
 * @return array
 */
function s3ConnectCheck($region, $bucket_name, $access_key, $secret_key)
{
    $is_error = false;
    $response = array();

    try {
        $credentials = new Credentials($access_key, $secret_key);

        try {
            $options = array(
                'region' => $region,
                'version' => 'latest',
                'credentials' => $credentials
            );

            $s3Client = new S3Client($options);
            $bucketsRegion = $s3Client->getBucketLocation(array(
                'Bucket' => $bucket_name
            ));

            $message = "연결 되었습니다.";

            if ($bucketsRegion['LocationConstraint'] !== $region) {
                $message = "버킷 지역을 확인후 다시 입력해주세요";
                $is_error = true;
            }
        } catch (S3Exception $awsException) {
            $is_error = true;
            $message = "연결에 실패했습니다. 버킷 이름과 key 값이 올바른지 확인해주세요.";
        }

        $response['error'] = $is_error;
        $response['message'] = $message;
    } catch (Exception $e) {
        $response['error'] = $is_error;
        $response['code'] = $e->getCode();
        $response['message'] = $e->getMessage();
    }

    return $response;
}
