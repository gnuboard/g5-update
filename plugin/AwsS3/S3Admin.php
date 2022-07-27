<?php

namespace Gnuboard\Plugin\AwsS3;
class S3Admin
{
    protected $s3_service;
    private $is_admin_copy = false;
    public $admin_number = 100921;

    public function __construct(S3Service $s3_service)
    {
        $this->add_hooks();
        $this->s3_service = $s3_service;
    }

    /**
     * Class instance.
     * 싱글톤
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self(S3Service::getInstance());
        }
        return $instance;
    }

    function add_hooks()
    {
        // 관리자 메뉴 추가
        add_replace('admin_menu', array($this, 'add_admin_menu'), 1, 1);

        // 관리자 페이지 추가
        add_event('admin_get_page_aws_config', array($this, 'add_admin_page'), 1, 2);

        // 관리자에서 게시판 복사시 폴더 복사에 사용됨
        add_event('admin_board_copy_file', [$this, 'admin_board_copy_file'], 1, 2);
        // 관리자에서 게시판 복사시 폴더 복사에 사용됨
        add_replace('admin_copy_update_file', [$this, 'admin_copy_update_file'], 1, 4);

        // 관리자에서 게시판 삭제시 폴더 삭제에 사용됨
        add_event('admin_board_list_update', [$this, 'admin_board_list_update'], 1, 4);
    }

    public function add_admin_menu($admin_menu)
    {
        $admin_menu['menu100'][] = array(
            $this->admin_number,
            'aws S3 설정',
            G5_ADMIN_URL . '/view.php?call=aws_config',
            'aws_config'
        );

        return $admin_menu;
    }

    public function add_admin_page()
    {
        require_once(G5_PLUGIN_PATH . '/AwsS3/admin/index.php');
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

        if ($bo_table === $target_table) {
            return false;
        }

        $prefix = G5_DATA_DIR . '/file/' . $bo_table . '/';
        $new_path = G5_DATA_DIR . '/file/' . $target_table . '/';


        $lists = $this->s3_service->get_paginator('ListObjects', [
            'Bucket' => G5_S3_BUCKET_NAME,
            'Prefix' => $prefix,
        ]);

        foreach ($lists as $list) {
            if (!isset($list['Contents']) || empty($list['Contents'])) {
                continue;
            }

            foreach ($list['Contents'] as $object) {
                $new_key = str_replace($prefix, $new_path, $object['Key']);

                $this->s3_service->move_file($object['Key'], $new_key);
            }
        }
    }

    /**
     * 관리자에서 게시판 복사시 폴더 복사에 사용됨
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
                    $this->s3_service->delete_folder($tmp_bo_table);
                }
            }
        }
    }

    /**
     * S3 설정 파일 생성및 수정
     */
    public function create_s3_config($s3_access_key, $s3_secret_key, $s3_bucket_name, $s3_region, $s3_is_acl_use)
    {
        $file = G5_DATA_PATH . '/' . S3CONFIG_FILE;
        $f = fopen($file, 'wb');

        fwrite($f, "<?php" . PHP_EOL);
        fwrite($f, "if (!defined('_GNUBOARD_')) exit;" . PHP_EOL);
        fwrite($f, "define('G5_S3_ACCESS_KEY', '" . addcslashes($s3_access_key, "\\'") . "');" . PHP_EOL);
        fwrite($f, "define('G5_S3_SECRET_KEY', '" . addcslashes($s3_secret_key, "\\'") . "');" . PHP_EOL);
        fwrite($f, "define('G5_S3_BUCKET_NAME', '" . addcslashes($s3_bucket_name, "\\'") . "');" . PHP_EOL);
        fwrite($f, "define('G5_S3_REGION', '" . addcslashes($s3_region, "\\'") . "');" . PHP_EOL);
        fwrite($f, "define('G5_S3_IS_USE_ACL', '" . addcslashes($s3_is_acl_use, "\\'") . "');" . PHP_EOL);

        fclose($f);
        @chmod($file, G5_FILE_PERMISSION);
    }
}