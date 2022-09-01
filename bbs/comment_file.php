<?php

/**
 * 댓글 파일 입출력
 */
include_once('./_common.php');

define('COMMENT_FIlE_DIR', 'comment');
define('COMMENT_FILE_PATH', G5_DATA_PATH . '/' . 'comment');
define('COMMENT_FILE_SiZE', 1000000);

//입력
$input_data = json_decode(file_get_contents('php://input'), true);
if (!empty($input_data)) {
    $token = isset($input_data['token']) ? $input_data['token'] : '';
    $bo_table = isset($input_data['bo_table']) ? $input_data['bo_table'] : '';
    $comment_id = isset($input_data['comment_id']) ? $input_data['comment_id'] : null;
    $file_name = isset($input_data['file_name']) ? $input_data['file_name'] : '';
    $wr_password = isset($input_data['wr_password']) ? $input_data['wr_password'] : '';
    $w = isset($input_data['w']) ? $input_data['w'] : ''; //파일 작업 모드
    $wr_id = isset($input_data['wr_id']) ? $input_data['wr_id'] : '';
} else {
    $token = isset($_POST['token']) ? $_POST['token'] : '';
}

// 세션 인증 토큰
$comment_token = trim(get_session('ss_comment_token'));
$mb_id = get_session('ss_mb_id');

if (empty($token) || empty($comment_token) || $comment_token != $token) {
    echo json_encode('올바른 방법으로 이용해 주십시오.');
    exit;
}

if (!is_dir(COMMENT_FILE_PATH)) {
    @mkdir(COMMENT_FILE_PATH);
    chmod(COMMENT_FILE_PATH, G5_DIR_PERMISSION);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($w == 'a') {
        if (empty($bo_table)) {
            echo json_encode(array());
            exit;
        }
        $select_all_sql = 'select wr_id, file_id, file_source_name as original_name, file_name, file_size, file_download_count, comment_id, save_time, wr_option, wr_password from ' . G5_TABLE_PREFIX . 'write_' . sql_real_escape_string(
                $bo_table
            ) . ' as board inner join ' . G5_TABLE_PREFIX . 'comment_file as comment_file ' .
            ' on  board.wr_id = comment_file.comment_id where wr_parent = ' . sql_real_escape_string(
                $wr_id
            ) . ' order by wr_id';

        $result = sql_query($select_all_sql);
        $response_row = array();

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                if ($row['wr_option'] === 'secret') {
                    $ss_name = 'ss_secret_comment_' . $bo_table;
                    if (!get_session($ss_name)) {
                        continue;
                    }
                }
                unset($row['wr_password']);
                unset($row['wr_option']);
                $row['original_name'] = html_purifier($row['original_name']);
                $response_row[$row['comment_id']]['files'][] = $row;
            }
        }
        $response = $response_row;
        if (empty($response)) {
            $response['files'] = array();
        }
        echo json_encode($response);
        exit;
    }
    if ($w === 'r') {
        if (empty($comment_id)) {
            $msg = '댓글 id가 없습니다.';
            echo json_encode($msg);
        }

        if (G5_MYSQLI_USE && function_exists('mysqli_connect') && version_compare(PHP_VERSION, '5.3.0') >= 0) { //바인딩 쿼리
            $link = $GLOBALS['g5']['connect_db'];

            $select_sql = "select file_name, file_source_name, comment_id, mb_id, save_time, wr_option, file_size
            from " . G5_TABLE_PREFIX . "comment_file as comment_file inner join " . G5_TABLE_PREFIX . 'write_' . sql_real_escape_string(
                    $bo_table
                ) . ' as board  on comment_file.comment_id = board.wr_id 
            where comment_id = ?';

            /**
             * @var $stmt mysqli_stmt
             */
            $stmt = $link->prepare($select_sql);

            $stmt->bind_param('i', $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $select_sql = "select file_name, file_source_name, comment_id, mb_id, save_time, wr_option, file_size
            from " . G5_TABLE_PREFIX . "comment_file as comment_file inner join " . G5_TABLE_PREFIX . 'write_' . sql_real_escape_string(
                    $bo_table
                ) . ' as board  on comment_file.comment_id = board.wr_id 
            where comment_id =' . sql_real_escape_string($comment_id);
            $result = sql_query($select_sql);
        }

        if (sql_num_rows($result) < 1) {
            $response['files'] = array();
            echo json_encode($response);
            exit;
        }

        $response = array();

        while ($row = sql_fetch_array($result)) {
            $get_save_month = date('ym', strtotime($row['save_time']));
            $comment_file_url = G5_DATA_URL . '/' . 'comment' . '/' . $get_save_month;
            $end_point_url = run_replace('replace_url', $comment_file_url);
            $pathinfo = pathinfo($row['file_name']);

            if ($row['wr_option'] === 'secret') {
                $ss_name = 'ss_secret_comment_' . $bo_table;
                if (!get_session($ss_name)) {
                    $response['files'] = array();
                    echo json_encode($response['files']);
                    exit;
                }
            }

            $save_fileinfo = array(
                'original_name' => html_purifier($row['file_source_name']),
                'file_name' => $row['file_name'],
                'file_type' => $pathinfo['extension'],
                'file_size' => $row['file_size'],
                'comment_id' => $row['comment_id'],
                'end_point' => $end_point_url
            );
            $response['files'][] = $save_fileinfo;
        }
        echo json_encode($response);
        exit;
    }

    if ($w === 'u') {//upload

        $delay_seconds = 2;
        if (isset($_SESSION['ss_datetime'])) {
            if (($_SESSION['ss_datetime'] >= (G5_SERVER_TIME - $delay_seconds))) {
                $response = array(
                    'is_error' => true,
                    'msg' => '너무 빠른 시간내에 연속해서 올릴 수 없습니다.'
                );

                header('HTTP/1.1 429 Too Many Requests', true, 429);
                echo json_encode($response);
                exit;
            }
        }

        $file_count = count($_FILES['comment_file']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['comment_file']['size'][$i] > COMMENT_FILE_SiZE) {
                continue;
            }
        }

        run_event('before_upload_comment_file', $_FILES['comment_file']);

        $upload_result = comment_file_uploader($_FILES['comment_file']);
        if (isset($upload_result['is_error'])) {
            header('400 Bad Request ', true, 400);
        }

        run_event('after_upload_comment_file', $upload_result);

        set_session("ss_datetime", G5_SERVER_TIME);

        echo json_encode($upload_result);
        exit;
    }

    if ($w === 'd') { //delete 삭제
        $link = $GLOBALS['g5']['connect_db'];

        if ($comment_id === null) { // 댓글 저장전 삭제할 때
            $is_valid = false;
            if (G5_MYSQLI_USE && function_exists('mysqli_connect')) {
                $delete_sql = 'delete from ' . G5_TABLE_PREFIX . 'comment_file where file_name = ? and comment_id IS NULL';
                /**
                 * @var mysqli_stmt $stmt
                 */
                $stmt = $link->prepare($delete_sql);
                $stmt->bind_param('s', $file_name);
                $result = $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $is_valid = true;
                }
            } else {
                $delete_sql = 'delete from ' . G5_TABLE_PREFIX . 'comment_file where file_name = "' . sql_real_escape_string(
                        $file_name
                    )
                    . '" and comment_id IS NULL';
                sql_query($delete_sql);

                if (mysql_affected_rows($link) > 0) {
                    $is_valid = true;
                }
            }

            if ($is_valid) {
                $get_save_month = $ym = date('ym', G5_SERVER_TIME);
                $file_path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $file_name;
                $is_delete = @unlink($file_path);
            }
        } else { // 댓글 수정시 삭제 할 때
            $is_valid = false;
            $select_sql = 'select wr_password, mb_id, file_name, save_time from ' . G5_TABLE_PREFIX . 'comment_file as comment_file inner join ' . G5_TABLE_PREFIX . 'write_' . sql_real_escape_string(
                    $bo_table
                ) .
                ' as board on comment_file.comment_id = board.wr_id where comment_id = ' . sql_real_escape_string(
                    $comment_id
                ) . ' and bo_table = "' . sql_real_escape_string(
                    $bo_table
                ) . '" and comment_file.file_name = "' . sql_real_escape_string(
                    $file_name
                ) . '"';

            $row = sql_fetch($select_sql);

            //인증
            $is_auth = false;
            if ($is_admin == 'super') {
                $is_auth = true;
            }

            if (!empty($row['wr_password'])) { //익명 유저게시판 쓰는 경우
                if (check_password($wr_password, $row['wr_password'])) {
                    $is_auth = true;
                }
            } elseif (get_session('ss_mb_id') === $row['mb_id']) { //가입된 유저
                $is_auth = true;
            }

            if ($is_auth === false) {
                $response = array(
                    'is_error' => true,
                    'msg' => '잘못된 요청입니다.'
                );
                header('HTTP/1.1 401 Unauthorized', true, 401);
                echo json_encode($response);
                exit;
            }
            //인증 끝

            run_event('before_delete_comment_file', $bo_table, $row);

            if (isset($row['file_name']) && ($row['file_name'] === $file_name)) {
                $delete_sql = 'delete from ' . G5_TABLE_PREFIX . 'comment_file where comment_id = ' . sql_real_escape_string(
                        $comment_id
                    ) . ' and bo_table = "' . sql_real_escape_string($bo_table) . '"';
                sql_query($delete_sql);
                $get_save_month = date('ym', strtotime($row['save_time']));
                $file_path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $file_name;
                $is_delete = @unlink($file_path);
            }
        }

        $response = array();
        if ($is_delete || $is_valid) {
            $response['is_error'] = false;
        } else {
            $response['is_error'] = true;
        }
        $response['msg'] = $is_delete;
        $response['file_path'] = isset($file_path) ? $file_path : null;
        echo json_encode($response);
        exit;
    }

    if ($w === 'download') {
        ob_end_clean();

        @include_once($board_skin_path . '/comment_download.skin.php'); //사용자 코드

        $select_sql = 'select file_name, file_download_count, file_source_name, save_time from ' . G5_TABLE_PREFIX . 'comment_file  where comment_id =' . sql_real_escape_string(
                $comment_id
            ) . ' and file_name = "' . sql_real_escape_string(
                $file_name
            ) . '" and bo_table = "' . sql_real_escape_string($bo_table) . '"';

        $result = sql_query($select_sql);
        if ($result) {
            $count = sql_num_rows($result);
            if ($count !== 1) {
                exit;
            }

            while ($fileinfo_row = sql_fetch_array($result)) {
                $file_download_count = $fileinfo_row['file_download_count'];
                $file_name = $fileinfo_row['file_name'];
                $file_original_name = rawurlencode($fileinfo_row['file_source_name']);
                $get_save_month = date('ym', strtotime($fileinfo_row['save_time']));
            }
            $ss_name = 'ss_down_' . $bo_table . '_' . $comment_id;
            if (!get_session($ss_name)) {
                $file_download_count++;
                $update_download_count_sql = 'update ' . G5_TABLE_PREFIX . 'comment_file set file_download_count = ' . sql_real_escape_string(
                        $file_download_count
                    )
                    . ' where file_name = "' . sql_real_escape_string(
                        $file_name
                    ) . '" and bo_table = "' . sql_real_escape_string($bo_table) . '"';
                sql_query($update_download_count_sql);

                set_session($ss_name, true);
            }

            $file_path = COMMENT_FILE_PATH . "/{$get_save_month}/" . $file_name;
            $file_exist_check = file_exists($file_path);

            run_event('download_file_header', $fileinfo_row, $file_exist_check);

            if ($file_exist_check === false) {
                exit;
            }

            if (stripos($_SERVER['HTTP_USER_AGENT'], "Firefox") !== false) {
                header("content-type: file/unknown");
                header("content-length: " . filesize($file_path));
                header("content-disposition: attachment; filename=\"" . $file_original_name . "\"");
                header("content-description: php generated data");
            } else {
                header("content-type: file/unknown");
                header("content-length: " . filesize($file_path));
                header("content-disposition: attachment; filename=\"$file_original_name\"");
                header("content-description: php generated data");
            }
            header("pragma: no-cache");
            header("expires: 0");
            flush();

            $fp = fopen($file_path, 'rb');

            $download_rate = 10;

            while (!feof($fp)) {
                print fread($fp, round($download_rate * 1024));
                flush();
                usleep(1000);
            }

            fclose($fp);
            flush();
        }
    }
}

/**
 * 댓글 첨부 파일 업로더
 * @param $file
 * @return array
 */
function comment_file_uploader($file)
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

        $file_name_pathinfo = pathinfo(get_safe_filename(strtolower($file['name'][$i])));
        $file_extension = $file_name_pathinfo['extension'];

        if (preg_match('/(\.jpe?g|\.gif|\.png|\.webp|bmp)$/i', $file['name'][$i])) {
            $imageinfo = getimagesize($file['tmp_name'][$i]);
            if ($imageinfo === false) {
                if (!($file_extension === 'webp' && PHP_VERSION_ID < 70100)) {
                    $response = array(
                        'is_error' => true,
                        'msg' => '잘못된 사진 파일입니다.'
                    );
                }
                return json_encode($response);
            }

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
            write_comment_file_info($file['name'][$i], $result_save_file, $file['size'][$i]);
            $comment_file_url = G5_DATA_URL . '/' . 'comment' . '/' . $ym;
            $end_point_url = run_replace('replace_url', $comment_file_url);
            $save_fileinfo = array(
                'original_name' => $file['name'][$i],
                'file_name' => $result_save_file,
                'file_type' => $file_extension,
                'end_point' => $end_point_url
            );
            $response['files'][] = $save_fileinfo;
        }
    }

    return $response;
}

/**
 * 코맨트 DB에 파일정보기록
 * @param string $file_original_name
 * @param string $file_save_name
 * @return void
 */
function write_comment_file_info($file_original_name, $file_save_name, $file_size)
{
    if (G5_MYSQLI_USE && function_exists('mysqli_connect')) {
        $write_file_info_sql = 'INSERT INTO ' . G5_TABLE_PREFIX . 'comment_file  set file_source_name = ?, file_name = ?, save_time = ?, file_size = ?';
        /**
         * @var $stmt mysqli_stmt
         */
        $stmt = $GLOBALS['connect_db']->prepare($write_file_info_sql);
        $time = G5_TIME_YMDHIS;
        $stmt->bind_param('sssi', $file_original_name, $file_save_name, $time, $file_size);
        $stmt->execute();
    } else {
        $write_file_info_sql = 'INSERT INTO ' . G5_TABLE_PREFIX . 'comment_file  set file_source_name = "' . sql_real_escape_string(
                $file_original_name
            ) .
            '", file_name =  "' . sql_real_escape_string(
                $file_save_name
            ) . '" , save_time = "' . G5_TIME_YMDHIS . '"' . ', file_size = ' . sql_real_escape_string($file_size);
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