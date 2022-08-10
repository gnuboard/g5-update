<?php

/**
 * 댓글 파일 입출력
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH . 'editor/smarteditor2/photo_uploader/popup/UploadHandler.php');

define('COMMENT_FIlE_DIR', 'comment');
define('COMMENT_FILE_PATH', G5_DATA_PATH . '/' .'comment');
define('COMMENT_FILE_SiZE', 1000000);

// 인증 토큰체크
$comment_token = trim(get_session('ss_comment_token'));
if (empty($_POST['token']) || empty($comment_token) || $comment_token != $_POST['token']) {
    alert('올바른 방법으로 이용해 주십시오.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_count = count($_FILES['comment_file']['tmp_name']);
    $_FILES['comment_file']['tmp_name'];

    for ($i = 0; $i < $file_count; $i++) {
        if (strpos($_FILES['comment_file']['name'], '.php') === false) {
            continue;
        }
        if (filesize($file) > COMMENT_FILE_SiZE) {
            continue;
        }
        comment_uploader($_FILES['comment_file']);
    }
}

function comment_uploader($file)
{

    $ym = date('ym', G5_SERVER_TIME);

    $comment_file_path = G5_DATA_PATH . COMMENT_FILE_PATH . $ym . '/' . $file['name'];
    $comment_file_url = G5_URL . COMMENT_FILE_PATH . $ym . '/' . $file['name'];
    if (preg_match('/(\.jpe?g|\.gif|\.png|\.webp|bmp)$/i', $file)) {
        $options = array(
            'upload_dir' => $comment_file_path,
            'upload_url' => $comment_file_url,
            'image_versions' => array()
        );

        $upload_handler = new UploadHandler($options);
        echo json_encode($upload_handler);
    } else {
        $is_success = move_uploaded_file($file, $comment_file_path);
       if($is_success){
            wirte_fileinfo_db($file,$file);
       }
    }
}

function wirte_fileinfo_db($file, $save_name)
{
    if (function_exists('mysqli_connect')) {
        global $bo_table;
        $write_file_info_sql = 'INSERT INTO comment_file  set comment_id = ?, file_source_name = ?, file_name = ?, bo_table = ?';
        /**
         * @var $stmt mysqli_stmt
         */
        $stmt = $GLOBALS['connect_db']->prepare($write_file_info_sql);
        $stmt->bind_param('iis', $comment_id, $file['name'], $save_name, $bo_table);
        $stmt->execute();
    } else {
        $write_file_info_sql = 'INSERT INTO comment_file  set comment_id = sql_real_escape_string($comment_id)';
        sql_query($write_file_info_sql);
    }

}