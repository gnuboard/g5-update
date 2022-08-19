<?php

/**
 * 댓글 파일 입출력
 */
include_once('./_common.php');

define('COMMENT_FIlE_DIR', 'comment');
define('COMMENT_FILE_PATH', G5_DATA_PATH . '/' . 'comment');
define('COMMENT_FILE_SiZE', 1000000);

// 인증 토큰체크
$comment_token = trim(get_session('ss_comment_token'));
if (empty($_POST['token']) || empty($comment_token) || $comment_token != $_POST['token']) {
    echo json_encode('올바른 방법으로 이용해 주십시오.');
    exit;
}

if (!is_dir(COMMENT_FILE_PATH)) {
    @mkdir(COMMENT_FILE_PATH);
    chmod(COMMENT_FILE_PATH, G5_DIR_PERMISSION);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($w)) {
        echo json_encode('설정 파라미터 w 값이 없습니다');
        exit;
    }
    if ($w === 'u') { //upload
        $file_count = count($_FILES['comment_file']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['comment_file']['size'][$i] > COMMENT_FILE_SiZE) {
                continue;
            }
        }
        $upload_result = comment_uploader($_FILES['comment_file']);
        run_event('upload_file', $upload_result);

        echo json_encode($upload_result);
        exit;
    }

    if ($w === 'd') { //삭제
        if (isset($file_url, $comment_id)) {
            $select_sql = "select * from " . G5_TABLE_PREFIX . 'comment_file where comment_id=' . $comment_id;
            $row = sql_fetch($select_sql);
            if (isset($row['file_name']) && strpos(basename($file_url), $row['file_name']) !== false) {
                str_replace(G5_DATA_URL, G5_DATA_PATH, $file_url);
                $result = unlink($file_url);
            }
        }
        $response = array();
        if (isset($result) && $result) {
            $response['is_success'] = true;
        } else {
            $response['is_success'] = false;
        }
        echo json_encode($response);
        exit;
    }
}

function comment_uploader($file)
{
    $ym = date('ym', G5_SERVER_TIME);
    $file_count = count($file['name']);
    $comment_file_path = COMMENT_FILE_PATH . "/{$ym}/";

    if (!is_dir($comment_file_path)) {
        @mkdir($comment_file_path);
        chmod($comment_file_path, G5_DIR_PERMISSION);
    }

    $response = array();
    for ($i = 0; $i < $file_count; $i++) {
        if (strpos($file['name'][$i], '.php') !== false) {
            continue;
        }

        if (preg_match('/(\.jpe?g|\.gif|\.png|\.webp|bmp)$/i', $file['name'][$i])) {
            $imageinfo = getimagesize($file['tmp_name'][$i]);
            $thumb_width = round($imageinfo[0] * 0.8);
            $thumb_height = round($imageinfo[1] * 0.8);
            $tmp_file_name = basename($file['tmp_name'][$i]);
            $tmp_path = dirname($file['tmp_name'][$i]);
            $thumbnail_file_name = thumbnail(
                $tmp_file_name,
                $tmp_path,
                $comment_file_path,
                $thumb_width,
                $thumb_height,
                false
            );

            $file_name_pathinfo = pathinfo(get_safe_filename(strtolower($file['name'][$i])));
            $new_file_name = file_name_generator($file_name_pathinfo['filename']);
            $temp_file_name_pathinfo = pathinfo($file['tmp_name'][$i]);

            /**
             * @var $save_file_name string 썸네일 아닌 파일이름
             */
            $save_file_name = str_replace(
                $file['tmp_name'][$i],
                $new_file_name . '.' . $file_name_pathinfo['extension'],
                $file['tmp_name'][$i]
            );
            $save_thumb_file_name = str_replace(
                $temp_file_name_pathinfo['filename'],
                $new_file_name,
                $thumbnail_file_name
            );
            if ($thumbnail_file_name === $temp_file_name_pathinfo['basename']) { //원본
                //thumbnail 함수는 webp와 gif 를 지원하지 않는다.
                if ($file_name_pathinfo['extension'] === 'webp' || $file_name_pathinfo['extension'] === 'gif') {
                    move_uploaded_file($file['tmp_name'][$i], "$comment_file_path/$thumbnail_file_name");
                }
                $is_success = rename(
                    "{$comment_file_path}/{$thumbnail_file_name}",
                    "{$comment_file_path}/{$save_file_name}"
                );
                $result_save_file = $save_file_name;
            } else { //원본과 썸네일
                move_uploaded_file($file['tmp_name'][$i], "{$comment_file_path}/{$save_file_name}");
                $is_success = rename(
                    "{$comment_file_path}/$thumbnail_file_name",
                    "{$comment_file_path}/{$save_thumb_file_name}"
                );
                $result_save_file = $save_thumb_file_name;
            }
        } else {
            //일반 파일
            $save_file_name = file_name_generator($file['name'][$i]);
            $is_success = move_uploaded_file($file['tmp_name'][$i], "{$comment_file_path}/{$save_file_name}");
            $result_save_file = $save_file_name;
        }

        if ($is_success) {
            write_file_name_comment_db($file['name'][$i], $result_save_file);
            $pathinfo = pathinfo($result_save_file);
            $comment_file_url = G5_DATA_URL . '/' . 'comment' . '/' . $ym;
            $end_point_url = run_replace('replace_url', $comment_file_url);
            $save_fileinfo = array(
                'original_name' => $file['name'][$i],
                'save_name' => $result_save_file,
                'file_type' => $pathinfo['extension'],
                'end_point' => $end_point_url
            );
            $response['files'][] = $save_fileinfo;
        }
    }

    return $response;
}

/**
 * 코맨트 DB에 파일정보기록
 * @param string $file_name
 * @param string $save_name
 * @return void
 */
function write_file_name_comment_db($file_name, $save_name)
{
    if (function_exists('mysqli_connect')) {
        $write_file_info_sql = 'INSERT INTO ' . G5_TABLE_PREFIX . 'comment_file  set file_source_name = ?, file_name = ?';
        /**
         * @var $stmt mysqli_stmt
         */
        $stmt = $GLOBALS['connect_db']->prepare($write_file_info_sql);
        $stmt->bind_param('ss', $file_name, $save_name);
        $stmt->execute();
    } else {
        $write_file_info_sql = 'INSERT INTO ' . G5_TABLE_PREFIX . 'comment_file  set file_source_name = ' . sql_real_escape_string(
                $file_name
            ) .
            ', file_name = ' . sql_real_escape_string($save_name);
        sql_query($write_file_info_sql);
    }
}

function file_name_generator($file_name)
{
    $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    shuffle($chars_array);
    $shuffle = implode('', $chars_array);
    return sha1(microtime()) . '_' . substr($shuffle, 0, 8) . '_' . replace_filename(get_safe_filename($file_name));
}