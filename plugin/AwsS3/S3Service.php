<?php

namespace Gnuboard\Plugin\AwsS3;

if (!defined('_GNUBOARD_')) {
    exit;
} // 개별 페이지 접근 불가

if (file_exists(G5_DATA_PATH . '/s3config.php')) {
    include_once(G5_DATA_PATH . '/s3config.php');
}
require_once(G5_LIB_PATH . '/AwsSdk/aws-autoloader.php');

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class S3Service
{
    private $access_key = '';
    private $secret_key = '';
    private $region = '';
    private $bucket_name = '';

    /**
     * @var S3Client
     */
    private $s3_client;
    private $config;

    private $extra_item_field = 'aws_images';
    private $storage_prefix = 'aws_s3';
    private $shop_folder = 'item';
    public $table_name = 's3_config';

    // Hook 포함 클래스 작성 요령
    // https://github.com/Josantonius/PHP-Hook/blob/master/tests/Example.php
    // https://sir.kr/manual/g5/288

    /**
     * Class instance.
     * 싱글톤
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function __clone()
    {
        // 객체 클론 방지
    }

    public function __construct()
    {
        $this->config = $this->get_config();
        //s3 사용시에만 훅스 등록

        if ($this->config['s3_only_used_file'] == 1 && $this->config['s3_region'] && $this->config['s3_bucket_name'] && $this->config['s3_user_key'] && $this->config['s3_user_secret']) {
            $this->add_hooks();
        }
        //칼럼명에 - 안들어가게 방지
        $this->extra_item_field = preg_replace('/[^a-z0-9_]/i', '', $this->extra_item_field);
    }

    /**
     * 객체 생성시 설정값 불러오기
     * @return string[]
     */
    private function get_config()
    {
        /* @var string[] $config s3 설정
         *    [
         *      's3_bucket_name' => (string) 설정파일. Required.
         *      's3_region'      => (string) 설정파일. Required.
         *      's3_user_key'    => (string) 설정파일. Required.
         *      's3_user_secret' => (string) 설정파일. Required.
         *      's3_access_control_list' => (string) DB Required.
         *      's3_save_mydata'    => (int) 0 or 1 DB Required.
         *      's3_only_used_file' => (int) 0 or 1 DB Required.
         *    ]
         */
        if (file_exists(G5_DATA_PATH . '/s3config.php')) {
            $config = array(
                's3_bucket_name' => G5_S3_BUCKET_NAME,
                's3_region' => G5_S3_REGION,
                's3_user_key' => G5_S3_ACCESS_KEY,
                's3_user_secret' => G5_S3_SECRET_KEY,
                //아래는 테이블에 저장
                's3_access_control_list' => 'private',
                's3_save_mydata' => '0',
                's3_only_used_file' => '0'
            );
        } else {
            $config = array(
                's3_bucket_name' => $this->bucket_name,
                's3_region' => $this->region,
                's3_user_key' => $this->access_key,
                's3_user_secret' => $this->secret_key,
                's3_access_control_list' => 'private',
                's3_save_mydata' => '0',
                's3_only_used_file' => '0'
            );
        }
        $table_name = G5_TABLE_PREFIX . $this->table_name;
        $sql = "SHOW TABLES LIKE '{$table_name}'";
        $is_install = sql_fetch($sql, false);
        if (!$is_install) {
            $sql = get_db_create_replace(
                "CREATE TABLE IF NOT EXISTS `$table_name` (
				  `s3_access_control_list` varchar(50) NOT NULL DEFAULT '',
				  `s3_save_mydata` tinyint(4) NOT NULL DEFAULT '0',
				  `s3_only_used_file` tinyint(4) NOT NULL DEFAULT '1'
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
            );
            sql_query($sql, false);
            $sql = "INSERT INTO $table_name (`s3_access_control_list`, `s3_save_mydata`, `s3_only_used_file`) VALUES ('private', 0 ,0)";
            sql_query($sql);
        } else {
            $sql = "select * from $table_name";
            $result = sql_fetch($sql, false);
            $config['s3_access_control_list'] = $result['s3_access_control_list'];
            $config['s3_save_mydata'] = $result['s3_save_mydata'];
            $config['s3_only_used_file'] = $result['s3_only_used_file'];
        }

        return $config;
    }

    public function get_regions()
    {
        // https://docs.aws.amazon.com/ko_kr/general/latest/gr/rande.html
        return array(
            'ap-northeast-2' => '아시아 태평양(서울)',
            'us-east-1' => '미국 동부(버지니아 북부)',
            'us-east-2' => '미국 동부(오하이오)',
            'us-west-1' => '미국 서부(캘리포니아 북부)',
            'us-west-2' => '미국 서부(오레곤)',
            'ap-east-1' => '아시아 태평양(홍콩)',
            'ap-south-1' => '아시아 태평양(뭄바이)',
            'ap-southeast-1' => '아시아 태평양(싱가포르)',
            'ap-southeast-2' => '아시아 태평양(시드니)',
            'ap-northeast-1' => '아시아 태평양(도쿄)',
            'ap-northeast-3' => '아시아 태평양(오사카)',
            'ca-central-1' => '캐나다(중부)',
            'cn-north-1' => '중국(베이징)',
            'cn-northwest-1' => '중국(닝샤)',
            'eu-central-1' => 'EU(프랑크푸르트)',
            'eu-west-1' => 'EU(아일랜드)',
            'eu-west-2' => 'EU(런던)',
            'eu-west-3' => 'EU(파리)',
            'eu-north-1' => 'EU(스톡홀름)',
            'me-south-1' => '중동(바레인)',
            'sa-east-1' => '남아메리카(상파울루)',
            'us-gov-east-1' => 'AWS GovCloud (미국 동부)',
            'us-gov-west-1' => 'AWS GovCloud (US)',
        );
    }

    public function mime_content_type($filename)
    {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'webp' => 'image/webp',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            '7z' => 'application/x-7z-compressed',
            'gz' => 'application/gzip',
            'jar' => 'application/java-archive',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mpeg' => 'video/mpeg',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

            // font
            'ttf' => 'application/x-font-ttf',
            'woff' => 'application/x-font-woff'

        );

        $filenames = explode('.', $filename);
        $ext = strtolower(array_pop($filenames));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mimetype = finfo_file($finfo, $filename);
                finfo_close($finfo);
                return $mimetype;
            } else {
                return 'application/octet-stream';
            }
        }
    }

    public function s3_client()
    {
        if ($this->s3_client === null) {
            //Create a S3Client
            $this->s3_client = new S3Client(array(
                'region' => $this->config['s3_region'],
                'version' => 'latest',
                'credentials' => array(
                    'key' => $this->config['s3_user_key'],
                    'secret' => $this->config['s3_user_secret']
                )
            ));
        }

        return $this->s3_client;
    }

    /**
     * 연결 상태 확인
     * @return array or throw
     */
    public function get_s3_connect_status() {
        $is_error = false;
        $response = array();

        $access_key = $this->config['s3_user_key'];
        $secret_key = $this->config['s3_user_secret'];
        $region = $this->config['s3_region'];
        $bucket_name =$this->config['s3_bucket_name'];
        try {
            $credentials = new Credentials($access_key, $secret_key);

            try {
                $options = array(
                    'region' => $region,
                    'version' => 'latest',
                    'credentials' => $credentials
                );

                $s3_client = new S3Client($options);
                $bucket_region = $s3_client->getBucketLocation(array(
                    'Bucket' => $bucket_name
                ));

                $message = "연결 되었습니다.";

                if ($bucket_region['LocationConstraint'] !== $region) {
                    $message = "버킷 지역을 확인후 다시 입력해주세요";
                    $is_error = true;
                }
            } catch (S3Exception $s3Exception) {
                $is_error = true;
                $response['error'] = $is_error;
                $status_code = $s3Exception->getStatusCode();
                $error_message = $s3Exception->getAwsErrorMessage();
                $message = "http 상태코드: {$status_code}\nAWS 메시지: {$error_message}\n연결에 실패했습니다. 버킷 이름과 지역, key 값이 올바른지 확인해주세요.\n이름,지역, 키값이 올바른 경우 AWS 권한을 확인해주세요";
            }
            $response['error'] = $is_error;
            $response['message'] = $message;
        } catch (S3Exception $s3Exception) {
            $is_error = true;
            $response['error'] = $is_error;
            $status_code = $s3Exception->getStatusCode();
            $error_message = $s3Exception->getAwsErrorMessage();
            $message = "http 상태코드: {$status_code}\nAWS 메시지: {$error_message}\n 연결에 실패했습니다. 버킷 이름과 key 값이 올바른지 확인해주세요.\n 이름과 키값이 올바른 경우 AWS 권한을 확인해주세요";
            $response['message'] = $message;
        }

        return $response;
    }

    private function add_hooks()
    {
        // bbs/download.php 등에서 쓰일수가 있음
        add_event('download_file_header', array($this, 'download_file_header'), 1, 2);

        // 에디터에서 파일삭제시
        add_event('delete_editor_file', array($this, 'delete_editor_file'), 1, 2);

        // bbs/write_update.php 등에서 쓰일수가 있음
        add_replace('write_update_upload_array', array($this, 'upload_file'), 1, 5);

        add_replace('download_file_exist_check', array($this, 'file_exist_check'), 1, 2);

        // bbs/view_image.php 파일에서 쓰임
        add_replace('get_editor_content_url', array($this, 'replace_aws_url'), 1, 1);
        add_replace('get_file_board_url', array($this, 'replace_aws_url'), 1, 1);

        // 썸네일 생성시 파일 체크함수
        add_replace('get_file_thumbnail_tags', array($this, 'get_thumbnail_tags'), 1, 2);

        // 에디터 파일 url이 aws s3 url 이 맞는지 체크
        add_replace('get_editor_filename', array($this, 'get_url_filename'), 1, 2);

        // 게시판 리스트에서 썸네일 출력
        add_replace('get_list_thumbnail_info', array($this, 'get_list_thumbnail_info'), 1, 2);

        // 파일 삭제시 체크 bbs/delete.php, bbs/delete_all.php 에서 사용됨
        add_replace('delete_file_path', array($this, 'delete_file'), 1, 2);

        // 에디터 url
        add_replace('get_editor_upload_url', array($this, 'editor_upload_url'), 1, 3);

        // wr_content 등 내용에서 내 도메인 이미지 url 을 aws s3 https 로 변환
        add_replace('get_view_thumbnail', array($this, 'get_view_thumbnail'), 1, 1);

        // bbs/view_image.php 사용됨
        add_replace('exists_view_image', array($this, 'exists_view_image'), 1, 3);
        add_replace('get_view_imagesize', array($this, 'exists_view_image'), 2, 3);

        // 게시물 복사 또는 옮기기 bbs/move_update.php 에서 사용됨
        add_replace('bbs_move_update_file', array($this, 'bbs_move_update_file'), 1, 5);

        // 관리자에서 게시판 복사시 폴더 복사에 사용됨
        add_event('admin_board_copy_file', array($this, 'admin_board_copy_file'), 1, 2);
        // 관리자에서 게시판 복사시 폴더 복사에 사용됨
        add_replace('admin_copy_update_file', array($this, 'admin_copy_update_file'), 1, 4);

        // 관리자에서 게시판 삭제시 폴더 삭제에 사용됨
        add_event('admin_board_list_update', array($this, 'admin_board_list_update'), 1, 4);

        // 아래부터는 쇼핑몰에서만 사용
        // 쇼핑몰 관리자에서 상품추가시 첨부파일을 업로드
        add_event('shop_admin_itemformupdate', array($this, 'shop_admin_itemformupdate'), 1, 2);

        // 쇼핑몰 관리자에서 상품이미지 파일이 있는지 체크함, adm/shop_admin/itemform.php
        add_replace('shop_item_image_exists', array($this, 'shop_item_image_exists'), 1, 3);

        // 쇼핑몰 관리자에서 상품이미지 html tag를 리턴함, adm/shop_admin/itemform.php
        add_replace('shop_item_image_tag', array($this, 'shop_item_image_tag'), 1, 3);

        // 쇼핑몰 상품이미지 썸네일이미지를 리턴
        add_replace('get_it_thumbnail_tag', array($this, 'get_it_thumbnail_tag'), 1, 6);

        // 쇼핑몰 상품이미지 url 을 리턴
        add_replace('get_item_image_url', array($this, 'get_item_image_url'), 1, 3);

        // 쇼핑몰 상품이미지 파일 정보들을 리턴, shop/largeimage.php 에서 사용
        add_replace('get_image_by_item', array($this, 'get_image_by_item'), 1, 4);

        // 쇼핑몰 상품이미지 파일이 aws s3 저장소에 있는지 체크
        add_replace('is_exists_item_file', array($this, 'is_exists_item_file'), 1, 3);

        // 쇼핑몰 상품 리스트의 썸네일을 출력
        add_replace('get_it_image_tag', array($this, 'get_it_image_tag'), 1, 9);

        // 쇼핑몰 상품을 삭제시
        add_event('shop_admin_delete_item_file', array($this, 'delete_shop_file'), 1, 1);
    }

    public function bucket_exists($bucket)
    {
        return $this->s3_client()->doesBucketExist($bucket);
    }

    public function object_exists($bucket, $key, $options = array())
    {
        return $this->s3_client()->doesObjectExist($bucket, $key, $options);
    }

    public function get_object($args)
    {
        return $this->s3_client()->getObject($args);
    }

    public function get_object_url($bucket_name, $key)
    {
        return $this->s3_client()->getObjectUrl($bucket_name, $key);
    }

    public function put_object($args)
    {
        return $this->s3_client()->putObject($args);
    }

    public function delete_object($args)
    {
        return $this->s3_client()->deleteObject($args);
    }

    public function copy_object($args)
    {
        return $this->s3_client()->copyObject($args);
    }

    public function get_paginator($action, $args)
    {
        return $this->s3_client()->getPaginator($action, $args);
    }

    /**
     * 개체(파일)의 ACL 권한 부여
     * @param $file_key
     * @return string 권한명
     */
    public function file_acl($file_key = '')
    {
        // https://docs.aws.amazon.com/ko_kr/AmazonS3/latest/dev/acl-overview.html

        if ($this->config['s3_access_control_list'] === 'public-read') {
            return 'public-read';
        }

        // 확장자가 이미지, 비디오인 경우 퍼블릭 권한을 갖음
        if (preg_match('/(\.jpg|\.jpeg|\.gif|\.png|\.webp|\.bmp|\.mp4|\.webm)$/i', $file_key)) {
            return 'public-read';
        }

        return 'private';
    }

    public function storage()
    {
        return $this->storage_prefix . '_' . $this->config['s3_bucket_name'];
    }

    private function file_delete($filepath)
    {
        $replace_path = realpath($this->normalize_path($filepath));

        if (!preg_match('/\.\.\//i', $replace_path) && preg_match(
                '/' . preg_quote(G5_DATA_PATH, '/') . '/i',
                $replace_path
            ) !== false) {
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            @unlink($replace_path);
        }
    }

    public function normalize_path($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }
        return $path;
    }

    /**
     * @param $dirname
     * @return false|void
     */
    private function delete_folder($dirname)
    {
        if (!$this->s3_client()) {
            return false;
        }

        $prefix = G5_DATA_DIR . '/file/' . $dirname . '/';

        $results = $this->get_paginator('ListObjects', array(
            'Bucket' => $this->config['s3_bucket_name'],
            'Prefix' => $prefix
        ));

        foreach ($results as $result) {
            if (!isset($result['Contents']) || !$result['Contents']) {
                continue;
            }

            foreach ($result['Contents'] as $object) {
                $this->delete_object(array(
                    'Bucket' => $this->config['s3_bucket_name'],
                    'Key' => $object['Key']
                ));
            }
        }
    }

    private function move_file($oldfile, $newfile)
    {
        if ($oldfile === $newfile || !$this->s3_client()) {
            return false;
        }

        if ($this->object_exists($this->config['s3_bucket_name'], $newfile)) {
            return false;
        }

        $this->copy_object(array(
            'Bucket' => $this->config['s3_bucket_name'],
            'Key' => $newfile,
            'CopySource' => $this->config['s3_bucket_name'] . '/' . $oldfile,
            'ACL' => $this->file_acl($newfile),
        ));
    }

    /**
     * 관리자에서 게시판 삭제시 폴더 삭제에 사용됨
     * @param $act_button
     * @param $chk
     * @param $board_table
     * @param $qstr
     * @return void
     */
    public function admin_board_list_update($act_button, $chk, $board_table, $qstr)
    {
        global $is_admin;

        if (empty($chk) || empty($board_table)) {
            return;
        }

        if ($act_button === '선택삭제' && $is_admin === 'super') {
            $iMax = count($chk);
            for ($i = 0; $i < $iMax; $i++) {
                // 실제 번호를 넘김
                $k = $chk[$i];

                // include 전에 $bo_table 값을 반드시 넘겨야 함
                $tmp_bo_table = trim($board_table[$k]);

                if (preg_match("/^[A-Za-z0-9_]+$/", $tmp_bo_table)) {
                    $this->delete_folder($tmp_bo_table);
                }
            }
        }
    }

    /**
     * TODO
     * @param $files
     * @param $filename
     * @param $bo_table
     * @param $target_table
     * @return mixed
     */
    public function admin_copy_update_file($files, $filename, $bo_table, $target_table)
    {
        if ($this->is_admin_copy) {
            $ori_key = G5_DATA_DIR . '/file/' . $bo_table;
            $copy_key = G5_DATA_DIR . '/file/' . $target_table;

            $files['bf_fileurl'] = str_replace($ori_key, $copy_key, $files['bf_fileurl']);
            $files['bf_thumburl'] = str_replace($ori_key, $copy_key, $files['bf_thumburl']);
        }

        return $files;
    }

    //--- 쇼핑몰 관련

    /**
     * 쇼핑몰 추가 칼럼 생성
     * @return void
     */
    public function update_item_table()
    {
        global $g5;

        sql_query(
            " ALTER TABLE `{$g5['g5_shop_item_table']}` 
                    ADD COLUMN `{$this->extra_item_field}` text NOT NULL ",
            false
        );
    }

    /**
     * 추가 칼럼이 있는지 확인
     * @param $it array item (상품)
     * @return bool
     */
    public function check_extra_item_field($it)
    {
        if(isset($it[$this->extra_item_field])) {
            return true;
        }

        $this->update_item_table();
        return false;
    }



    /**
     * 쇼핑몰 관리자에서 상품이미지 파일이 있는지 체크함, adm/shop_admin/itemform.php
     * 상품 이미지 미리보기
     * @param $file_exists
     * @param $it
     * @param $i
     * @return bool|mixed
     */
    public function shop_item_image_exists($file_exists, $it, $i)
    {
        if (!$it['it_img' . $i] || !$this->s3_client()) {
            return $file_exists;
        }

        $aws_s3_key = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it['it_img' . $i];

        $aws_key_exists = $this->object_exists($this->config['s3_bucket_name'], $aws_s3_key);

        if ($file_exists && !$aws_key_exists) {
            $this->shop_admin_itemformupdate($it['it_id'], 'a');
        }

        if ($aws_key_exists) {
            return $aws_key_exists;
        }

        return $file_exists;
    }


    /**
     * 쇼핑몰 관리자에서 상품추가시 이미지를 업로드
     * @param $it_id
     * @param $w
     * @return false|void
     */
    public function shop_admin_itemformupdate($it_id, $w)
    {
        global $g5;

        if (!$this->s3_client()) {
            return false;
        }

        $it = get_shop_item($it_id, false);

        $img_arrays = array();

        for ($i = 1; $i <= 10; $i++) {
            if (!$it['it_img' . $i]) {
                continue;
            }

            $filepath = G5_DATA_PATH . '/' . $this->shop_folder . '/' . $it['it_img' . $i];
            $aws_s3_key = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it['it_img' . $i];

            $aws_key_exists = $this->object_exists($this->config['s3_bucket_name'], $aws_s3_key);

            if ($aws_key_exists) {
                $img_arrays['img' . $i] = $this->get_object_url(
                    $this->config['s3_bucket_name'],
                    G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it['it_img' . $i]
                );
            }

            if (!file_exists($filepath) || $aws_key_exists) {
                continue;
            }

            $upload_mime = $this->mime_content_type($filepath);

            // Upload data.
            $result = $this->put_object(array(
                'Bucket' => $this->config['s3_bucket_name'],
                'Key' => $aws_s3_key,
                'Body' => fopen($filepath, 'rb'),
                'ACL' => $this->file_acl($aws_s3_key),
                'ContentType' => $upload_mime,
            ));

            if (isset($result['ObjectURL']) && $result['ObjectURL']) {
                $img_arrays['img' . $i] = $result['ObjectURL'];

                if (!$this->config['s3_save_mydata']) {
                    $this->file_delete($filepath);
                }
            }
        }

        if (!isset($it[$this->extra_item_field])) {
            $this->update_item_table();
            $it[$this->extra_item_field] = '';
        }

        $item_extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));
        $before_infos = array();

        foreach ((array)$item_extra_infos as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if (stripos($key, 'img') === 0 && $it['it_img' . preg_replace('/^[0-9]/', '', $key)]) {
                $before_infos[$key] = $value;
            }
        }

        $merges = array_merge($img_arrays, $before_infos);
        $save_str = base64_encode(serialize($merges));

        if ($it[$this->extra_item_field] !== $save_str) {
            // item 테이블에서 필드 extra_item_field 를 업데이트합니다.

            $sql = " update {$g5['g5_shop_item_table']}
                        set {$this->extra_item_field} = '$save_str'
                      where it_id = '$it_id' ";

            sql_query($sql, false);
        }
    }


    /**
     * 쇼핑몰 핑몰 상품 이미지 url 리턴
     * @param $url
     * @param $it
     * @param $index
     * @return array|mixed|string|string[]
     */
    public function get_item_image_url($url, $it, $index)
    {
        if ($this->config['s3_only_used_file']) {
            return $this->replace_url($url);
        }

        if (!$this->check_extra_item_field($it)) {
            return $url;
        }

        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));

        if (isset($extra_infos['img' . $index]) && $extra_infos['img' . $index]) {
            $url = $extra_infos['img' . $index];
        }

        return $url;
    }

    /**
     * 쇼핑몰 상품이미지 파일이 aws s3 저장소에 있는지 체크
     * @param $is_exist_file
     * @param $it
     * @param $index
     * @return bool|mixed
     */
    public function is_exists_item_file($is_exist_file, $it, $index)
    {
        global $g5;

        if (!$this->check_extra_item_field($it)) {
            return $is_exist_file;
        }

        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));

        if ($it['it_img' . $index] && isset($extra_infos['img' . $index]) && $extra_infos['img' . $index] && $this->aws_s3_url_validate(
                $extra_infos['img' . $index]
            )) {
            return true;
        }

        if ($is_exist_file) {
            return $is_exist_file;
        }

        if ($this->s3_client()) {
            $file_key = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it['it_img' . $index];

            if ($this->object_exists($this->config['s3_bucket_name'], $file_key)) {
                $extra_infos['img' . $index] = $this->get_object_url($this->config['s3_bucket_name'], $file_key);
                $save_str = base64_encode(serialize($extra_infos));

                if ($it[$this->extra_item_field] !== $save_str) {
                    // item 테이블에서 필드 extra_item_field 를 업데이트합니다.

                    $sql = " update {$g5['g5_shop_item_table']}
                                set {$this->extra_item_field} = '$save_str'
                              where it_id = '{$it['it_id']}' ";

                    sql_query($sql, false);

                    $it = get_shop_item($it['it_id'], false);
                }

                return true;
            }

            return $is_exist_file;
        }

        return $is_exist_file;
    }

    /**
     * 쇼핑몰 상품 이미지 정보들을 리턴, shop/largeimage.php 에서 사용
     * @param $infos
     * @param $it
     * @param $index
     * @param $size
     * @return mixed
     */
    public function get_image_by_item($infos, $it, $index, $size = array())
    {
        $it = get_shop_item($it['it_id'], true);

        if (!$this->check_extra_item_field($it)) {
            return $infos;
        }

        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));

        if ($this->config['s3_only_used_file']) {
            $infos['imageurl'] = $this->replace_url($infos['imageurl']);
            $infos['imagehtml'] = '<img src="' . $infos['imageurl'] . '" alt="' . get_text(
                    $it['it_name']
                ) . '" id="largeimage_' . $index . '" class="aws_s3_image">';
        } else {
            if ($it['it_img' . $index] && isset($extra_infos['img' . $index]) && $extra_infos['img' . $index] && $this->aws_s3_url_validate(
                    $extra_infos['img' . $index]
                )) {
                $infos['imageurl'] = $extra_infos['img' . $index];
                $infos['imagehtml'] = '<img src="' . $extra_infos['img' . $index] . '" alt="' . get_text(
                        $it['it_name']
                    ) . '" id="largeimage_' . $index . '" class="aws_s3_image">';
            }
        }

        return $infos;
    }


    /**
     * 쇼핑몰 상품 리스트에서 썸네일 리턴
     *
     */
    public function get_it_image_tag(
        $img,
        $thumb = '',
        $it_id,
        $width,
        $height = 0,
        $anchor = false,
        $img_id = '',
        $img_alt = '',
        $is_crop = false
    ) {
        if ($thumb && !$this->config['s3_only_used_file']) {
            //  $this->config['s3_only_used_file'] 값이 false 이고 && 내 서버 공간에 썸네일이 존재한다면 aws s3에서 조회하지 않고 내 서버 파일의 썸네일을 리턴
            return $img;
        }

        $it = get_shop_item($it_id, true);

        if (!$this->check_extra_item_field($it)) {
            return $img;
        }

        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));
        $find_tag = $find_img = '';

        for ($i = 1; $i <= 10; $i++) {
            if ($it['it_img' . $i]) {
                $matches = array();

                if ($find_img = $this->get_it_thumbnail_by_index('', $i, $it, $width, $height)) {
                    preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $find_img, $matches);
                } else {
                    if (isset($extra_infos['img' . $i]) && stripos(
                            $extra_infos['img' . $i],
                            $it['it_img' . $i]
                        ) !== false && preg_match('/(\.gif|\.bmp)$/i', $extra_infos['img' . $i])) {
                        $matches[1] = $extra_infos['img' . $i];
                    }
                }

                if (isset($matches[1]) && $this->aws_s3_url_validate($matches[1])) {
                    preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $img, $tmps);
                    if (isset($tmps[1]) && stripos($tmps[1], G5_URL) !== false
                        && preg_match('/(\.jpg|\.jpeg|\.gif|\.png|\.bmp)$/i', $tmps[1])) {
                        $img = str_replace($tmps[1], $matches[1], $img);
                    }
                }

                break;
            }
        }

        return $img;
    }


    /**
     * 쇼핑몰 상품 썸네일 html tag 리턴
     *
     */
    public function get_it_thumbnail_tag($str, $img, $width, $height, $id, $is_crop)
    {
        $arr_ex = explode('/', $img);

        $it_id = $arr_ex[0];

        $it = get_shop_item($it_id, true);

        $extra_infos = array();
        if ($this->check_extra_item_field($it)) {
            $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));
        }

        if ($key = array_search($img, $it, true)) {
            $index = preg_replace('/[^0-9]/i', '', $key);

            if (!($str = $this->get_it_thumbnail_by_index('', $index, $it, $width, $height))) {
                if (isset($extra_infos['img' . $index]) && stripos(
                        $extra_infos['img' . $index],
                        $it['it_img' . $index]
                    ) !== false && $this->aws_s3_url_validate($extra_infos['img' . $index]) && preg_match(
                        '/(\.gif|\.bmp)$/i',
                        $extra_infos['img' . $index]
                    )) {
                    $str = '<img src="' . $extra_infos['img' . $index] . '" width="' . $width . '" height="' . $height . '" >';
                }
            }
        }

        return $str;
    }

    /**
     * shop_create_thumbnail
     * @param $img
     * @param $width
     * @param $height
     * @param $id
     * @param $is_crop
     * @return string
     */
    public function shop_create_thumbnail($img, $width, $height = 0, $id = '', $is_crop = false)
    {
        $file = G5_DATA_PATH . '/' . $this->shop_folder . '/' . $img;

        if (!(file_exists($file) && is_file($file))) {
            return '';
        }

        $size = @getimagesize($file);

        if ($size[2] < 1 || $size[2] > 3) {
            return '';
        }

        $str = '';
        $img_width = $size[0];
        $img_height = $size[1];
        $filename = basename($file);
        $filepath = dirname($file);

        if ($img_width && !$height) {
            $height = round(($width * $img_height) / $img_width);
        }

        $thumb = thumbnail(
            $filename,
            $filepath,
            $filepath,
            $width,
            $height,
            false,
            $is_crop,
            'center',
            false,
            $um_value = '80/0.5/3'
        );

        if ($thumb) {
            $file_url = str_replace(G5_PATH, G5_URL, $filepath . '/' . $thumb);
            $str = '<img src="' . $file_url . '" width="' . $width . '" height="' . $height . '"';
            if ($id) {
                $str .= ' id="' . $id . '"';
            }
            $str .= ' alt="">';
        }

        return $str;
    }

    /**
     * 쇼핑몰 상품 이미지 썸네일 가져오기
     * @param $image_tag
     * @param $index
     * @param $it
     * @param $width
     * @param $height
     * @return mixed|string
     */
    public function get_it_thumbnail_by_index($image_tag = '', $index, $it, $width, $height = 0)
    {
        global $g5;

        if (!$it['it_img' . $index]) {
            return '';
        }

        if (!isset($it[$this->extra_item_field])) {
            $this->update_item_table();
            $it[$this->extra_item_field] = '';
        }

        $array_thumb_key = $index . '_' . (string)$width . 'x' . (string)$height;
        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));
        $before_file_exists = false;

        if (isset($extra_infos['thumb' . $array_thumb_key]) && $extra_infos['thumb' . $array_thumb_key]) {
            $image_tag = '<img src="' . $extra_infos['thumb' . $array_thumb_key] . '"';
            $image_tag .= ' alt="" class="aws_s3_thumb">';
        } else {
            $download_path = G5_DATA_PATH . '/' . $this->shop_folder . '/' . $it['it_img' . $index];
            $file_key = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it['it_img' . $index];
            $before_file_exists = file_exists($download_path) ? true : false;

            if (!preg_match('/(\.jpg|\.jpeg|\.gif|\.png|\.webp|\.bmp)$/i', $file_key)) {
                return '';
            }

            if ($this->config['s3_only_used_file'] || !$before_file_exists) {
                if (!$this->s3_client()) {
                    return '';
                }

                $aws_key_exists = $this->object_exists($this->config['s3_bucket_name'], $file_key);

                if ($aws_key_exists) {
                    $thumb_str = '';

                    if ($before_file_exists) {
                        $thumb_str = $this->shop_create_thumbnail($it['it_img' . $index], $width, $height);
                        // 이미지가 있고 이미지 정보를 읽어올수 있다면
                    } else {
                        if ($image_info = $this->get_curl_image($download_path, $file_key)) {
                            $thumb_str = $this->shop_create_thumbnail($it['it_img' . $index], $width, $height);
                        }
                    }

                    // 이미지가 있으나 이미지 정보가 없거나 이미지 깨진 경우 썸네일을 만들수 없으므로, 매번 aws s3 에서 호출해야 하는 낭비가 온다.
                    // 이를 방지하고자 아무 이미지나 썸네일을 미리 만들어 놓는다.
                    if (!$thumb_str) {
                        $no_image_path = G5_PATH . '/img/no_img.png';

                        if (file_exists($no_image_path)) {
                            $filename = basename($no_image_path);
                            $filepath = dirname($no_image_path);
                            $item_path = G5_DATA_PATH . '/' . $this->shop_folder . '/' . $it['it_id'];
                            if ($thumb_tmp = thumbnail($filename, $filepath, $item_path, $width, $height, false)) {
                                $thumb_str = '<img src= "' . str_replace(
                                        G5_DATA_PATH,
                                        G5_DATA_URL,
                                        $item_path
                                    ) . '/' . $thumb_tmp . '" >';
                            }
                        }
                    }

                    if ($thumb_str) {
                        preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $thumb_str, $matches);

                        if (isset($matches[1]) && $matches[1]) {
                            $thumb_path = str_replace(G5_DATA_URL, G5_DATA_PATH, $matches[1]);
                            $thumb_key = G5_DATA_DIR . str_replace(G5_DATA_URL, '', $matches[1]);

                            $upload_mime = $this->mime_content_type($thumb_key);

                            if ($this->object_exists($this->config['s3_bucket_name'], $thumb_key)) {
                                $thumb_object_url = $this->get_object_url($this->config['s3_bucket_name'], $thumb_key);
                            } else {
                                // Upload thumbnail data.
                                $thumb_result = $this->put_object(array(
                                    'Bucket' => $this->config['s3_bucket_name'],
                                    'Key' => $thumb_key,
                                    'Body' => fopen($thumb_path, 'rb'),
                                    'ACL' => $this->file_acl($thumb_key),
                                    'ContentType' => $upload_mime,
                                ));

                                $thumb_object_url = $thumb_result['ObjectURL'];
                            }

                            // 썸네일 파일을 aws s3에 성공적으로 업로드 했다면, 호스팅 공간에서 삭제합니다.
                            if ($thumb_object_url) {
                                if (function_exists('gc_collect_cycles')) {
                                    gc_collect_cycles();
                                }

                                // 원래부터 파일이 없었고, 다운받은 파일이 있으면 다시 삭제
                                if (!$before_file_exists) {
                                    @unlink($thumb_path);
                                }

                                $it = get_shop_item($it['it_id'], false);
                                $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));

                                $extra_infos['thumb' . $array_thumb_key] = $thumb_object_url;

                                $base64_str = base64_encode(serialize($extra_infos));

                                $sql = " update `{$g5['g5_shop_item_table']}`
                                            set {$this->extra_item_field} = '$base64_str'
                                          where it_id = '{$it['it_id']}' ";

                                sql_query($sql, false);

                                $image_tag = '<img src="' . $thumb_object_url . '"';
                                $image_tag .= ' alt="" class="aws_s3_thumb">';
                            }
                        }
                    }

                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                    // 원래부터 파일이 없었고, 다운받은 파일이 있으면 다시 삭제
                    if (!$before_file_exists && file_exists($download_path)) {
                        @unlink($download_path);
                    }
                }
            }
        }

        return $image_tag;
    }


    /**
     * 쇼핑몰 상품 이미지 미리보기
     *
     */
    public function shop_item_image_tag($image_tag = '', $it, $i)
    {
        $item_file = $this->shop_folder . '/' . $it['it_img' . $i];
        if (file_exists(G5_DATA_PATH . '/' . $item_file) && !$this->config['s3_only_used_file']) {
            return $image_tag;
        }

        $image_path = 'https://' . $this->config['s3_bucket_name'] . '.s3.' . $this->config['region'] . '.amazonaws.com/' . G5_DATA_DIR;

        return '<img src="' . $image_path . '/' . $item_file . '" class="shop_item_preview_image aws_s3_image" >';
    }

    /**
     *
     * @param $bo_table
     * @param $target_table
     * @return false|void
     */
    public function admin_board_copy_file($bo_table, $target_table)
    {
        $this->is_admin_copy = true;

        if ($bo_table === $target_table || !$this->s3_client()) {
            return false;
        }

        $prefix = G5_DATA_DIR . '/file/' . $bo_table . '/';
        $new_path = G5_DATA_DIR . '/file/' . $target_table . '/';

        $lists = $this->get_paginator('ListObjects', array(
            'Bucket' => $this->config['s3_bucket_name'],
            'Prefix' => $prefix,
        ));

        foreach ($lists as $list) {
            if (!isset($list['Contents']) || empty($list['Contents'])) {
                continue;
            }

            foreach ($list['Contents'] as $object) {
                $new_key = str_replace($prefix, $new_path, $object['Key']);

                $this->move_file($object['Key'], $new_key);
            }
        }
    }

    /**
     * TODO
     * @param $fileurl
     * @return mixed|string
     */
    public function replace_aws_url($fileurl)
    {
        if (stripos($fileurl, G5_DATA_URL) === false) {
            return $fileurl;
        }

        // https://docs.aws.amazon.com/ko_kr/AmazonS3/latest/API/RESTBucketGET.html
        $aws_image_url = 'https://' . $this->config['s3_bucket_name'] . '.s3.' . $this->config['s3_region'] . '.amazonaws.com' . str_replace(
                G5_URL,
                '',
                $fileurl
            );

        if ($this->config['s3_only_used_file']) {
            return $aws_image_url;
        }

        $filepath = str_replace(G5_DATA_URL, G5_DATA_PATH, $fileurl);

        if ($this->exists_view_image(1, $filepath, '')) {
            return $aws_image_url;
        }

        return $fileurl;
    }

    /**
     * @param $files
     * @param $file_name
     * @param $bo_table
     * @param $move_bo_table
     * @param $insert_id
     * @return mixed
     */
    public function bbs_move_update_file($files, $file_name, $bo_table, $move_bo_table, $insert_id = 0)
    {
        if ($files['bf_fileurl'] && $files['bf_storage'] === $this->storage()) {
            if ($ori_filename = $this->get_url_filename('', @parse_url($files['bf_fileurl']))) {
                $ori_key = G5_DATA_DIR . '/file/' . $bo_table . '/' . $ori_filename;
                $copy_key = G5_DATA_DIR . '/file/' . $move_bo_table . '/' . $file_name;

                if ($this->s3_client()) {
                    $result = $this->copy_object(array(
                        'Bucket' => $this->config['s3_bucket_name'],
                        'Key' => $copy_key,
                        'CopySource' => $this->config['s3_bucket_name'] . '/' . $ori_key,
                        'ACL' => $this->file_acl($this->config['s3_bucket_name'] . '/' . $ori_key),
                    ));

                    if (isset($result['ObjectURL']) && $result['ObjectURL']) {
                        $files['bf_fileurl'] = $result['ObjectURL'];

                        if ($files['bf_thumburl'] && $thumbname = $this->get_url_filename(
                                '',
                                @parse_url($files['bf_thumburl'])
                            )) {
                            $ori_thumb_key = G5_DATA_DIR . '/file/' . $bo_table . '/' . $thumbname;
                            $copy_thumb_key = G5_DATA_DIR . '/file/' . $move_bo_table . '/' . str_replace(
                                    'thumb-',
                                    'thumb-copy-' . $insert_id . '-',
                                    $thumbname
                                );

                            $result2 = $this->copy_object(array(
                                'Bucket' => $this->config['s3_bucket_name'],
                                'Key' => $copy_thumb_key,
                                'CopySource' => $this->config['s3_bucket_name'] . '/' . $ori_thumb_key,
                                'ACL' => $this->file_acl($this->config['s3_bucket_name'] . '/' . $ori_thumb_key),
                            ));

                            if (isset($result2['ObjectURL']) && $result2['ObjectURL']) {
                                $files['bf_thumburl'] = $result['ObjectURL'];
                            }
                        }
                    }
                }
            }
            /* TODO delete
            $parses = parse_url($files['bf_fileurl']);

            if( stripos($parses['host'], $this->config['s3_bucket_name'].'.s3.') !== false && stripos($parses['host'], 'amazonaws.com') !== false ){
                $file_key = preg_replace('/^\/(\/)?/', '', $parses['path']);

                $files['bf_fileurl'] = '';
                $files['bf_thumburl'] = '';
            }
            */
        }

        return $files;
    }

    public function file_exist_check($bool, $fileinfo)
    {
        if ($bool === false && $fileinfo['bf_fileurl']) {
            $aws_s3_key = G5_DATA_DIR . '/file/' . $fileinfo['bo_table'] . '/' . basename($fileinfo['bf_fileurl']);

            if ($this->s3_client()) {
                if ($this->object_exists($this->config['s3_bucket_name'], $aws_s3_key)) {
                    return true;
                }
            }
        }

        return $bool;
    }

    // https://stackoverflow.com/questions/41189477/preg-replace-image-src-using-callback

    /**
     * wr_content 등 내용에서 내 도메인 이미지 url 을 aws s3 https 로 변환
     * @param $contents
     * @return array|mixed|string|string[]|null
     */
    public function get_view_thumbnail($contents)
    {
        if (class_exists('DOMDocument') && method_exists(
                'DOMDocument',
                'loadHTML'
            ) && $this->config['s3_only_used_file']) {
            $contents = preg_replace_callback(
                "/(<img[^>]*src *= *[\"']?)([^\"']*)/i",
                array($this, 'replace_url'),
                $contents
            );
        }

        return $contents;
    }

    public function replace_url($matches)
    {
        $replace_url = 'https://' . $this->config['s3_bucket_name'] . '.s3.' . $this->config['s3_region'] . '.amazonaws.com/' . G5_DATA_DIR;

        if (is_array($matches)) {
            $matches[2] = str_replace(G5_DATA_URL, $replace_url, $matches[2]);

            return $matches[1] . $matches[2];
        }

        return str_replace(G5_DATA_URL, $replace_url, $matches);
    }


    public function get_thumbnail_tags($thumb_tag = '', $file_array)
    {
        global $board, $g5;

        if ($file_array['path'] && $file_array['file'] && !($file_array['bf_fileurl'] || $file_array['bf_thumburl'])) {
            $filepath = str_replace(G5_URL, '', $file_array['path'] . '/' . $file_array['file']);

            // 내 서버에 해당 파일이 있으면 리턴
            if (!$this->config['s3_only_used_file'] && file_exists($filepath)) {
                return $file_array;
            }

            $s3_key = preg_replace('/^\/(\/)?/', '', $filepath);
            $is_check = ($file_array['bf_storage'] === 'no') ? false : true;

            $queryString = parse_url(htmlspecialchars_decode($file_array['href']));
            $queryString = $queryString['query'];
            $args = array();
            parse_str($queryString, $args);

            if ($is_check && $this->s3_client()) {
                if ($url = $this->get_object_url($this->config['s3_bucket_name'], $s3_key)) {
                    $file_array['bf_fileurl'] = $url;

                    $extension = strtolower(pathinfo($file_array['file'], PATHINFO_EXTENSION));
                    $thumb_width = isset($board['bo_image_width']) ? (int)$board['bo_image_width'] : 0;
                    $data_path = str_replace(G5_URL, '', $file_array['path']);

                    // 이미지가 jpg, png 이면 썸네일을 체크
                    if (in_array($extension, array('jpg', 'jpeg', 'gif', 'png'))
                        && $thumb_width && (int)$file_array['image_width'] > $thumb_width) {
                        // 썸네일 높이
                        $thumb_height = round(
                            ($thumb_width * $file_array['image_height']) / $file_array['image_width']
                        );

                        $arguments = array(
                            'bo_table' => $args['bo_table'],
                            'wr_id' => $args['wr_id'],
                            'data_path' => $data_path,
                            'edt' => false,
                            'filename' => $file_array['file'],
                            'filepath' => str_replace(G5_DATA_URL, G5_DATA_PATH, $file_array['path']),
                            'thumb_width' => $thumb_width,
                            'thumb_height' => $thumb_height,
                            'is_create' => false,
                            'is_crop' => true,
                            'crop_mode' => 'center',
                            'is_sharpen' => false,
                            'um_value' => '',
                        );

                        if ($thumb_info = $this->get_list_thumbnail_info($arguments, array())) {
                            $thumb_path_file = str_replace(G5_DATA_URL, G5_DATA_PATH, $thumb_info['src']);
                            $upload_mime = $this->mime_content_type($thumb_path_file);

                            $thumb_key = G5_DATA_DIR . str_replace(G5_DATA_URL, '', $thumb_info['src']);

                            // Upload thumbnail data.
                            $thumb_result = $this->put_object(array(
                                'Bucket' => $this->config['s3_bucket_name'],
                                'Key' => $thumb_key,
                                'Body' => fopen($thumb_path_file, 'rb'),
                                'ACL' => $this->file_acl($thumb_key),
                                'ContentType' => $upload_mime,
                            ));

                            // 썸네일 파일을 aws s3에 성공적으로 업로드 했다면, 호스팅 공간에서 삭제합니다.
                            if (isset($thumb_result['ObjectURL']) && $thumb_result['ObjectURL']) {
                                $file_array['bf_thumburl'] = $thumb_result['ObjectURL'];

                                if (!$this->config['s3_save_mydata']) {
                                    $this->file_delete($thumb_path_file);
                                }

                                $sql = " update {$g5['board_file_table']}
                                            set bf_fileurl = '" . $file_array['bf_fileurl'] . "',
                                                 bf_thumburl = '" . $file_array['bf_thumburl'] . "',
                                                 bf_storage = '" . $this->storage() . "'
                                          where bo_table = '{$args['bo_table']}'
                                                    and wr_id = '{$args['wr_id']}'
                                                    and bf_no = '{$args['no']}' ";

                                sql_query($sql);
                            }
                        }
                    }
                }
            }

            if (!$file_array['bf_fileurl']) {
                // 내서버 또는 aws S3 저장소에 파일이 없다면, 파일 테이블의 bf_storage 필드에 no를 기록하여 S3 저장소를 다시 체크하지 않게 합니다.

                $sql = " update {$g5['board_file_table']}
                            set bf_storage = 'no'
                          where bo_table = '{$args['bo_table']}'
                                    and wr_id = '{$args['wr_id']}'
                                    and bf_no = '{$args['no']}' ";
                sql_query($sql);
            }
        }

        if ((isset($file_array['bf_fileurl']) && $file_array['bf_fileurl']) || (isset($file_array['bf_thumburl']) && $file_array['bf_thumburl'])) {
            $thumburl = (isset($file_array['bf_thumburl']) && $file_array['bf_thumburl']) ? $file_array['bf_thumburl'] : $file_array['bf_fileurl'];

            $thumb_tag = '<a href="' . G5_BBS_URL . '/view_image.php?bo_table=' . $board['bo_table'] . '&amp;fn=' . urlencode(
                    $file_array['file']
                ) . '" target="_blank" class="view_image"><img src="' . $thumburl . '" alt="' . get_text(
                    $file_array['content']
                ) . '"/></a>';
        }

        return $thumb_tag;
    }

    /**
     *
     * @param string $download_path
     * @param $file_key
     * @return array|false
     */
    public function get_curl_image($download_path, $file_key)
    {
        // https://docs.aws.amazon.com/ko_kr/AmazonS3/latest/API/RESTBucketGET.html
        $image_url = 'https://' . $this->config['s3_bucket_name'] . '.s3.' . $this->config['s3_region'] . '.amazonaws.com/' . $file_key;

        if (stripos($image_url, "https") != 0 || strlen($image_url) > 255) {
            $image_url = '';
        }

        if (!$image_url) {
            return array();
        }

        $curlUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0";

        $curl = curl_init();
        $err_status = '';

        $fp = fopen($download_path, 'wb');
        curl_setopt($curl, CURLOPT_URL, $image_url);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_USERAGENT, $curlUserAgent);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 2);
        curl_setopt(
            $curl,
            CURLOPT_FOLLOWLOCATION,
            true
        ); // Follow redirects, the number of which is defined in CURLOPT_MAXREDIRS
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_exec($curl);
        $err_status = curl_error($curl);
        curl_close($curl);
        fclose($fp);

        $image_info = array();

        if ($err_status === '') {
            $image_info = @getimagesize($download_path);

            if ($image_info === null) {
                $image_info = array();
            }

            //TODO
            // $mime_type = isset($image_info["mime"]) ? $image_info["mime"] : '';
            //if (!array_key_exists($mime_type, self::mime_content_type($mime_type))) {
            //   return array();
            //}
        }

        return $image_info;
    }

    public function get_list_thumbnail_info($thumbnail_info = array(), $arguments)
    {
        $bo_table = isset($arguments['bo_table']) ? $arguments['bo_table']: '';
        $wr_id = isset($arguments['wr_id']) ? $arguments['wr_id'] : '';
        $data_path = isset($arguments['data_path']) ? $arguments['data_path'] : '';
        $edt = isset($arguments['edt']) ? $arguments['edt'] : '';
        $filename = isset($arguments['filename']) ? $arguments['filename'] : '';
        $source_path = $target_path = isset($arguments['filepath']) ? $arguments['filepath'] : '';
        $thumb_width = isset($arguments['thumb_width'])? $arguments['thumb_width'] : '';
        $thumb_height = isset($arguments['thumb_height'])? $arguments['thumb_height']: '';
        $is_create = isset($arguments['is_create'])? $arguments['is_create']: '';
        $is_crop = isset($arguments['is_crop']) ? $arguments['is_crop'] : '';
        $crop_mode = isset($arguments['crop_mode'])? $arguments['crop_mode'] : '';
        $is_sharpen = isset($arguments['is_sharpen'])? $arguments['is_sharpen'] : '';
        $um_value = isset($arguments['um_value'])? $arguments['um_value'] : '';

        $tname = '';

        if (!$source_path && stripos($data_path, '/' . G5_EDITOR_DIR . '/') !== false) {
            $edt = true;
            $source_path = $target_path = G5_PATH . preg_replace(
                    '/^\/.*\/' . G5_DATA_DIR . '/',
                    '/' . G5_DATA_DIR,
                    dirname($data_path)
                );
        }

        // 원본 파일이 내 호스팅에 있다면 리턴
        if (file_exists($source_path . '/' . $filename)) {
            return $thumbnail_info;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 이미지가 아니면 리턴
        if (!in_array($extension, array('jpg', 'jpeg', 'gif', 'png', 'webp'))) {
            return $thumbnail_info;
        }

        $thumb_filename = preg_replace("/\.[^\.]+$/i", "", $filename); // 확장자제거
        $thumb_file = "$target_path/thumb-{$thumb_filename}_{$thumb_width}x{$thumb_height}." . $extension;

        // 썸네일 파일이 내 호스팅에 있다면 파일이름을 남김
        if (file_exists($thumb_file)) {
            $tname = basename($thumb_file);
        } else {
            if (is_Dir($source_path) && $this->s3_client()) {
                $download_path = $source_path . '/' . $filename;
                $file_key = G5_DATA_DIR . str_replace(G5_DATA_PATH, '', $download_path);

                $image_info = $this->get_curl_image($download_path, $file_key);

                if (!$image_info) {
                    $no_image_path = G5_PATH . '/img/no_img.png';

                    if (file_exists($no_image_path)) {
                        // 노 이미지로 썸네일 파일을 만들어 두번 다시 s3.amazonaws.com 에서 파일을 찾지 않도록 합니다.
                        @copy($no_image_path, $thumb_file);
                        @chmod($thumb_file, G5_FILE_PERMISSION);
                    }

                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                    // 생성한 파일 삭제
                    @unlink($download_path);

                    return array();
                }

                if (file_exists($download_path)) {
                    $tname = thumbnail(
                        $filename,
                        $source_path,
                        $target_path,
                        $thumb_width,
                        $thumb_height,
                        $is_create,
                        $is_crop,
                        $crop_mode,
                        $is_sharpen,
                        $um_value
                    );

                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                    // 다운받은 파일은 다시 삭제
                    @unlink($download_path);
                }
            }
        }

        if ($tname) {
            if ($edt) {
                // 오리지날 이미지
                $ori = G5_URL . $data_path;
                // 썸네일 이미지
                $src = G5_URL . str_replace($filename, $tname, $data_path);
            } else {
                $ori = G5_DATA_URL . '/file/' . $bo_table . '/' . $filename;
                $src = G5_DATA_URL . '/file/' . $bo_table . '/' . $tname;
            }

            $thumbnail_info = array(
                "src" => $src,
                "ori" => $ori,
                "alt" => ''
            );
        }

        return $thumbnail_info;
    }

    /**
     * 파일 주소를 바꿔주는 유틸 함수.
     * @param $url
     * @return array|string|string[]|null
     */
    public function fileurl_replace_key($url)
    {
        $queryString = @parse_url($url);

        $path = preg_replace('/^\/.*\/' . G5_DATA_DIR . '/', G5_DATA_DIR, $queryString['path']);

        return preg_replace('/^\/(\/)?/', '', $path);
    }

    /**
     * aws s3 에서 파일삭제
     * @param $filepath
     * @param $args
     * @return mixed
     */
    public function delete_file($filepath, $args = array())
    {
        if ($args['bf_fileurl'] && stripos($args['bf_storage'], 'aws_s3') !== false && $this->s3_client()) {
            $keyname = G5_DATA_DIR . '/file/' . $args['bo_table'] . '/' . $args['bf_file'];

            $result = $this->delete_object(array(
                'Bucket' => $this->config['s3_bucket_name'],
                'Key' => $keyname
            ));

            if ($args['bf_thumburl']) {
                $result = $this->delete_object(array(
                    'Bucket' => $this->config['s3_bucket_name'],
                    'Key' => $this->fileurl_replace_key($args['bf_thumburl']),
                ));
            }
        }

        return $filepath;
    }

    /**
     * aws s3 에서 쇼핑몰 썸네일 모두 삭제
     * 영카트 기본폴더 확인 후 썸네일 조회 후 삭제
     * @return void
     */
    public function delete_all_shop_thumbnail()
    {
        if ($this->s3_client()) {
            $filePrefix = G5_DATA_DIR . '/' . $this->shop_folder . '/';

            $this->delete_thumbnamil_by_prefix($filePrefix);
        }
    }

    /**
     * 쇼핑몰에서 상품삭제시 해당 상품번호의 썸네일 삭제
     * 영카트 기본폴더 확인후 썸네일 조회 후 삭제
     * @return void
     */
    public function delete_shop_thumbnail_by_id($number)
    {
        if ($this->s3_client()) {
            $filePrefix = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $number . '/';

            $this->delete_thumbnamil_by_prefix($filePrefix);
        }
    }

    /**
     * aws s3 쇼핑몰 상품 파일 삭제
     * @param int $it_id 상품번호
     * @return void
     */
    public function delete_shop_file($it_id)
    {
        $it = get_shop_item($it_id, true);

        if (!$this->check_extra_item_field($it)) {
            return;
        }

        $extra_infos = unserialize(base64_decode($it[$this->extra_item_field]));

        if ($extra_infos && $this->s3_client()) {
            foreach ((array)$extra_infos as $info) {
                if ($this->aws_s3_url_validate($info)) {
                    $filename = $this->get_url_filename('', @parse_url($info));
                    $aws_s3_key = G5_DATA_DIR . '/' . $this->shop_folder . '/' . $it_id . '/' . $filename;

                    $this->delete_object(array(
                        'Bucket' => $this->config['s3_bucket_name'],
                        'Key' => $aws_s3_key
                    ));
                }
            }
        }

        //해당 상품번호의 썸네일도 같이 삭제
        $this->delete_shop_thumbnail_by_id($it_id);
    }

    /**
     * url이 AWS 주소인지 유효성검사
     * @param $url
     * @return bool
     */
    public function aws_s3_url_validate($url)
    {
        if (preg_match('/^https:/i', $url) && stripos(
                $url,
                $this->config['s3_bucket_name'] . '.s3.'
            ) !== false && stripos($url, 'amazonaws.com') !== false) {
            return true;
        }

        return false;
    }

    /**
     * file url 이 aws s3 url 이 맞는지 확인
     */
    public function get_url_filename($filename, $parses)
    {
        if (!$filename && isset($parses['host']) && 'https' === $parses['scheme']) {
            if ($this->aws_s3_url_validate('https://' . $parses['host'])) {
                $filename = basename($parses['path']);
            }
        }

        return $filename;
    }

    /**
     * 에디터로 업로드한 url 얻기
     * @param $fileurl
     * @param $filepath
     * @param $args
     * @return string url
     */
    public function editor_upload_url($fileurl, $filepath, $args = array())
    {
        if ($this->s3_client && file_exists($filepath)) {
            $file_key = $this->fileurl_replace_key($fileurl);
            $upload_mime = $this->mime_content_type($filepath);

            // Upload thumbnail data.
            $result = $this->put_object(array(
                'Bucket' => $this->config['s3_bucket_name'],
                'Key' => $file_key,
                'Body' => fopen($filepath, 'rb'),
                'ACL' => $this->file_acl($file_key),
                'ContentType' => $upload_mime,
            ));

            if (isset($result['ObjectURL'])) {
                if (!$this->config['s3_save_mydata']) {
                    $this->file_delete($filepath);
                }
                return $result['ObjectURL'];
            }
        }

        return $fileurl;
    }

    /**
     *
     * bbs/view_image.php 에 사용됨
     * @param $files
     * @param $filepath
     * @param $editor_file
     * @return array|int|mixed
     */
    public function exists_view_image($files, $filepath, $editor_file)
    {
        static $cache = null;

        $tmp_key = md5($filepath);

        if (isset($cache[$tmp_key])) {
            return $cache[$tmp_key];
        }

        if ($this->s3_client()) {
            $aws_s3_key = G5_DATA_DIR . str_replace(G5_DATA_PATH, '', $filepath);

            if ($this->object_exists($this->config['s3_bucket_name'], $aws_s3_key)) {
                if (is_array($files)) {
                    $cache[$tmp_key] = array(0, 0, 'is_exists' => true);
                } else {
                    $cache[$tmp_key] = 1;
                }

                return $cache[$tmp_key];
            }
        }

        return $files;
    }

    /**
     * 에디터에서 올린 파일 삭제시
     * @param $file_path
     * @param $is_success
     * @return void
     */
    public function delete_editor_file($file_path, $is_success = false)
    {
        if (!$is_success && stripos($file_path, G5_DATA_PATH) === 0 && $this->s3_client()) {
            $keyname = $this->fileurl_replace_key($file_path);

            $result = $this->delete_object(array(
                'Bucket' => $this->config['s3_bucket_name'],
                'Key' => $keyname
            ));
        }
    }

    // bbs/download.php 등에서 쓰일수가 있음
    public function download_file_header($fileinfo, $file_exist_check)
    {
        if (!$file_exist_check) {
            $aws_s3_key = G5_DATA_DIR . '/file/' . $fileinfo['bo_table'] . '/' . basename($fileinfo['bf_fileurl']);

            if ($this->s3_client()) {
                $result = $this->get_object(array(
                    'Bucket' => $this->config['s3_bucket_name'],
                    'Key' => $aws_s3_key
                ));

                $original_name = urlencode($fileinfo['bf_source']);

                header('Content-Description: File Transfer');
                //this assumes content type is set when uploading the file.
                header('Content-Type: ' . $result['ContentType']);
                header('Content-Disposition: attachment; filename=' . $original_name);
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');

                //send file to browser for download.
                echo $result['Body'];

                exit;
            }
        }
    }

    /**
     * bbs/write_update.php 등에서 쓰일수가 있음
     * @param $upload_info array 업로드된 파일의 정보
     */
    public function upload_file($upload_info, $filepath, $board, $wr_id, $w = '')
    {
        //global $board; TODO

        $return_value = array(
            'fileurl' => '',
            'thumburl' => '',
            'storage' => '',
        );

        $aws_s3_key = G5_DATA_DIR . str_replace(G5_DATA_PATH, '', $filepath);

        if ($this->s3_client()) {
            $upload_mime = $this->mime_content_type($filepath);
            $thumb_result = array();

            // Upload data.
            $result = $this->put_object(array(
                'Bucket' => $this->config['s3_bucket_name'],
                'Key' => $aws_s3_key,
                'Body' => fopen($filepath, 'rb'),
                'ACL' => $this->file_acl($aws_s3_key),
                'ContentType' => $upload_mime,
            ));

            // 이미지 파일이면 TODO 이미지 파일 확인
            if ($result['ObjectURL'] && $upload_info && ($upload_mime === 'image/png' || $upload_mime === 'image/jpeg')) {
                $size = $upload_info['image'];

                if ($size && isset($board['bo_image_width']) && $size[0] > $board['bo_image_width']) {
                    $thumb_width = $board['bo_image_width'];

                    // jpg 이면 exif 체크
                    if ($size[2] == 2 && function_exists('exif_read_data')) {
                        $degree = 0;
                        $exif = @exif_read_data($upload_file);
                        if (!empty($exif['Orientation'])) {
                            switch ($exif['Orientation']) {
                                case 8:
                                    $degree = 90;
                                    break;
                                case 3:
                                    $degree = 180;
                                    break;
                                case 6:
                                    $degree = -90;
                                    break;
                            }

                            // 세로사진의 경우 가로, 세로 값 바꿈
                            if ($degree == 90 || $degree == -90) {
                                $tmp = $size;
                                $size[0] = $tmp[1];
                                $size[1] = $tmp[0];
                            }
                        }
                    }

                    // 썸네일 높이
                    $thumb_height = round(($thumb_width * $size[1]) / $size[0]);
                    $thumb_name = basename($filepath);
                    $thumb_path = dirname($filepath);

                    if ($thumb_file = thumbnail(
                        $thumb_name,
                        $thumb_path,
                        $thumb_path,
                        $thumb_width,
                        $thumb_height,
                        false
                    )) {
                        $thumb_key = G5_DATA_DIR . str_replace(G5_DATA_PATH, '', $thumb_path . '/' . $thumb_file);

                        // Upload thumbnail data.
                        $thumb_result = $this->put_object(array(
                            'Bucket' => $this->config['s3_bucket_name'],
                            'Key' => $thumb_key,
                            'Body' => fopen($thumb_path . '/' . $thumb_file, 'rb'),
                            'ACL' => $this->file_acl($thumb_key),
                            'ContentType' => $upload_mime,
                        ));

                        //썸네일 파일을 aws s3에 성공적으로 업로드 했다면, 호스팅 공간에서 삭제합니다.
                        if (isset($thumb_result['ObjectURL']) && $thumb_result['ObjectURL']) {
                            if (!$this->config['s3_save_mydata']) {
                                $this->file_delete($thumb_path . '/' . $thumb_file);
                            }
                        }
                    }
                }
            }

            //파일을 aws s3에 성공적으로 업로드 했다면, 호스팅 공간에서 삭제합니다.
            if (!$this->config['s3_save_mydata']) {
                $this->file_delete($filepath);
            }

            $return_value['fileurl'] = $result['ObjectURL'];
            $return_value['thumburl'] = isset($thumb_result['ObjectURL']) ? $thumb_result['ObjectURL'] : '';
            $return_value['storage'] = $this->storage();
        }

        return array_merge($upload_info, $return_value);
    }

    /**
     * AWS S3 에서 특정폴더 이하 썸네일 파일을 삭제하는 함수.
     * @param string $filePrefix 파일경로(s3객체 키)
     * @return void
     */
    public function delete_thumbnamil_by_prefix($filePrefix)
    {
        $files = $this->s3_client()->listObjects(array(
            'Bucket' => $this->config['s3_bucket_name'],
            'Prefix' => $filePrefix
        ));

        if (!isset($files['Contents'])) {
            return;
        }

        $files = $files['Contents'];
        foreach ($files as $file) {
            if (strpos($file['Key'], 'thumb-') !== false) {
                $this->delete_object(array(
                    'Bucket' => $this->config['s3_bucket_name'],
                    'Key' => $file['Key']
                ));
            }
        }
    }

}

S3Service::getInstance();
