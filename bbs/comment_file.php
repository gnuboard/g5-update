<?php

/**
 * 댓글 파일 입출력
 */
if (isset($_POST['board_skin_path'])) {
    $board_skin_path = null;
}

include_once('./_common.php');

// 응답 상태값 상수.
const FAIL = false;
const SUCCESS = true;

//입력
$input_data = json_decode(file_get_contents('php://input'), true);
if (!empty($input_data)) {
    $token = $input_data['token'] ?? '';
    $bo_table = isset($input_data['bo_table']) ? stripslashes($input_data['bo_table']) : '';
    $comment_id = isset($input_data['comment_id']) ? (int)$input_data['comment_id'] : null;
    $file_name = $input_data['file_name'] ?? '';
    $wr_password = $input_data['wr_password'] ?? '';
    $w = $input_data['w'] ?? ''; //파일 작업 모드
    $wr_id = isset($input_data['wr_id']) ? (int)$input_data['wr_id'] : '';
} else {
    $token = $_POST['token'] ?? '';
    $bo_table = isset($bo_table) ? stripslashes($bo_table) : null;
}

// 세션 인증 토큰
$comment_token = trim(get_session('ss_comment_token'));
$mb_id = get_session('ss_mb_id');

if (empty($token) || empty($comment_token) || $comment_token != $token) {
    response_to_json('올바른 방법으로 이용해 주십시오', FAIL);
    http_response_code(400);
    exit;
}

$board = get_board_db($bo_table, true);

if (!defined('COMMENT_FIlE_DIR')) {
    define('COMMENT_FIlE_DIR', 'comment');
}
define('COMMENT_FILE_PATH', G5_DATA_PATH . '/' . COMMENT_FIlE_DIR);
define('COMMENT_FILE_COUNT_LIMIT', $board['bo_cf_count']);
define('COMMENT_FILE_SiZE', $board['bo_cf_size_limit']);
define('COMMENT_FILE_IMG_RESIZE', $board['bo_cf_resize']);

$is_late_delete = isset($board['bo_cf_is_late_delete']) && ($board['bo_cf_is_late_delete'] == 1);
$upload_level = $board['bo_cf_upload_level'];
$download_level = $board['bo_cf_download_level'];
$member_level = $member['mb_level'];

if (!isset($g5['comment_file_table'])) {
    $g5['comment_file_table'] = G5_TABLE_PREFIX . 'comment_file';
}

if (!is_dir(COMMENT_FILE_PATH)) {
    @mkdir(COMMENT_FILE_PATH);
    chmod(COMMENT_FILE_PATH, G5_DIR_PERMISSION);
    create_comment_file_table();
}

if (!isset($board['bo_cf_count'])) {
    add_comment_setting_columns();
}

$link = $GLOBALS['g5']['connect_db'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($w == 'all') {
        if (empty($bo_table) || empty($wr_id)) {
            echo json_encode(array());
            response_to_json('올바른 방법으로 이용해주십시오', FAIL);
            http_response_code(400);
            exit;
        }

        $select_all_sql = 'select wr_id, file_id, file_source_name as original_name, file_name, file_size, file_download_count, comment_id, save_time, wr_option, wr_password 
            from ' . G5_TABLE_PREFIX . 'write_' . $bo_table . ' as board inner join ' . $g5['comment_file_table'] . ' as comment_file on  board.wr_id = comment_file.comment_id 
            where comment_file.is_delete = 0 and wr_parent = ? order by wr_id';
        /**
         * @var $stmt mysqli_stmt
         */
        $stmt = $link->prepare($select_all_sql);
        $stmt->bind_param('i', $wr_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $response = array();

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                if ($is_admin !== 'super' && $row['wr_option'] === 'secret') {
                    $ss_name = 'ss_secret_comment_' . $bo_table;
                    if (!get_session($ss_name)) {
                        continue;
                    }
                }
                unset($row['wr_password'], $row['wr_option']);
                $row['original_name'] = html_purifier($row['original_name']);
                $response[$row['comment_id']]['files'][] = $row;
            }
        }

        if (empty($response)) {
            $response['files'] = array();
        }
        echo json_encode($response);
        exit;
    }

    if ($w === 'r') {
        if (empty($comment_id)) {
            response_to_json('댓글 id가 없습니다.', FAIL);
            http_response_code(400);
            exit;
        }

        $select_sql = "select file_name, file_source_name, comment_id, mb_id, save_time, wr_option, file_size
        from " . $g5['comment_file_table'] . " as comment_file inner join " . G5_TABLE_PREFIX . 'write_' . sql_real_escape_string(
                $bo_table
            ) . ' as board  on comment_file.comment_id = board.wr_id 
        where comment_id = ? and is_delete = 0';

        /**
         * @var $stmt mysqli_stmt
         */
        $stmt = $link->prepare($select_sql);
        $stmt->bind_param('i', $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();


        if (mysqli_num_rows($result) < 1) {
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

            if ($is_admin !== 'super' && $row['wr_option'] === 'secret') {
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

    if ($w === 'u') { //upload 업로드
        if (empty($bo_table) || (count($_FILES['comment_file']['name']) == 0)) {
            http_response_code(400);
            echo response_to_json('잘못된 요청입니다', FAIL);
            exit;
        }

        if ($member_level < $upload_level) {
            http_response_code(401);
            echo response_to_json('권한이 없습니다.', FAIL);
            exit;
        }

        $delay_seconds = 2; //연속해서 올릴수 없게 지연시간(초 단위)
        if (isset($_SESSION['ss_datetime']) && ($_SESSION['ss_datetime'] >= (G5_SERVER_TIME - $delay_seconds))) {
            http_response_code(429);
            echo response_to_json('너무 빠른 시간내에 연속해서 올릴 수 없습니다.', FAIL);
            exit;
        }

        $file_count = count($_FILES['comment_file']['name']);
        if ($file_count > COMMENT_FILE_COUNT_LIMIT) {
            http_response_code(400);
            echo response_to_json('최대' . COMMENT_FILE_COUNT_LIMIT . '개 까지 업로드 가능합니다.', FAIL);
            exit;
        }

        run_event('before_upload_comment_file', $_FILES['comment_file']);

        $invalid_file_list = array();
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['comment_file']['size'][$i] > COMMENT_FILE_SiZE) {
                $invalid_file_list[] = $_FILES['comment_file']['name'];
                unset(
                    $_FILES['comment_file']['name'][$i],
                    $_FILES['comment_file']['size'][$i],
                    $_FILES['comment_file']['full_path'][$i],
                    $_FILES['comment_file']['type'][$i],
                    $_FILES['comment_file']['tmp_name'][$i],
                    $_FILES['comment_file']['error'][$i]
                );
            }
        }

        $upload_result = comment_file_uploader($_FILES['comment_file']);
        if (((isset($upload_result['is_success']) && ($upload_result['is_success'] === FAIL)) || count($invalid_file_list) > 0)) {
            $allow_file_size = $board['bo_cf_size_limit'] > 0 ? $board['bo_cf_size_limit'] / 1024 / 1024 : 0;
            $upload_result['error_file_list'] = $invalid_file_list;
            $upload_result['is_success'] = FAIL;
            $upload_result['allow_file_size'] = round($allow_file_size, 3);

            http_response_code(400);
        }

        run_event('after_upload_comment_file', $upload_result);

        set_session("ss_datetime", G5_SERVER_TIME);

        echo json_encode($upload_result);
        exit;
    }

    if ($w === 'd') { //delete 삭제

        if ($comment_id === null) { // 댓글 저장전 삭제할 때
            $is_valid = false;
            if ($is_late_delete) {
                $sql = 'update  ' . $g5['comment_file_table'] . ' set is_delete = 1 where file_name = ? and comment_id IS NULL';
                $stmt = $link->prepare($sql);
                $stmt->bind_param('s', $file_name);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    $is_success = SUCCESS;
                    $msg = '삭제되었습니다.';
                } else {
                    $is_success = FAIL;
                    $msg = '삭제에 실패했습니다.';
                }
                echo response_to_json($msg, $is_success);
                exit;
            }

            $delete_sql = 'delete from ' . $g5['comment_file_table'] . ' where file_name = ? and comment_id IS NULL';
            /**
             * @var mysqli_stmt $stmt
             */
            $stmt = $link->prepare($delete_sql);
            $stmt->bind_param('s', $file_name);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $is_valid = true;
            }

            $is_delete = false;
            if ($is_valid) {
                $get_save_month = $ym = date('ym', G5_SERVER_TIME);
                $is_delete = file_delete($file_name, $get_save_month);
            }
        } else { // 댓글 수정시 삭제 할 때

            $is_valid = false;
            $select_sql = 'select wr_password, mb_id, file_name, save_time from ' . $g5['comment_file_table'] . ' as comment_file inner join '
                . G5_TABLE_PREFIX . 'write_' . $bo_table
                . ' as board on comment_file.comment_id = board.wr_id where comment_id = ? and bo_table = ? and comment_file.file_name = ?';
            /**
             * @var $stmt mysqli_stmt
             */
            $stmt = $link->prepare($select_sql);
            $stmt->bind_param('iss', $comment_id, $bo_table, $file_name);
            $is_run = $stmt->execute();
            $result = $stmt->get_result();
            if ($stmt->affected_rows < 1) {
                $is_valid = false;
                $msg = '삭제되지 않았습니다.';
                $is_success = FAIL;
                echo response_to_json($msg, $is_success);
                exit;
            }

            $row = sql_fetch_array($result);

            //인증 관련
            $is_auth = false;
            if ($is_admin == 'super') {
                $is_auth = true;
            }

            if (!empty($row['wr_password'])) { //익명 유저게시판인 경우 비밀번호 확인
                if (check_password($wr_password, $row['wr_password'])) {
                    $is_auth = true;
                }
            } elseif (get_session('ss_mb_id') === $row['mb_id']) { //로그인한 본인 댓글인지 확인
                $is_auth = true;
            }

            if ($is_auth === false) {
                http_response_code(401);
                echo response_to_json('잘못된 요청입니다.', FAIL);
                exit;
            }
            //인증 끝

            run_event('before_delete_comment_file', $bo_table, $row);

            if (isset($row['file_name']) && ($row['file_name'] === $file_name)) {
                if ($is_late_delete) { //나중 삭제.
                    $update_sql = 'update ' . $g5['comment_file_table'] . ' set is_delete = 1 where comment_id = ? and bo_table = ?  and file_name = ?';

                    $stmt = $link->prepare($update_sql);
                    $stmt->bind_param('sss', $comment_id, $bo_table, $file_name);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $msg = '삭제되었습니다.'; // 나중 삭제지만 메시지만 띄움.
                        $is_success = SUCCESS;
                    } else {
                        $msg = '삭제되지 않았습니다.';
                        $is_success = FAIL;
                    }
                    echo response_to_json($msg, $is_success);
                    exit;
                }

                $delete_sql = 'delete from ' . $g5['comment_file_table'] . ' where comment_id = ?
                     and bo_table = ? and file_name = ?';

                $stmt = $link->prepare($delete_sql);
                $stmt->bind_param('sss', $comment_id, $bo_table, $file_name);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $is_valid = true;
                }

                $get_save_month = date('ym', strtotime($row['save_time']));
                $is_delete = file_delete($file_name, $get_save_month);
            }
        }

        $response = array();
        if (($is_delete === true) || $is_valid) {
            $is_success = SUCCESS;
            $msg = '삭제되었습니다.';
        } else {
            http_response_code(400);
            $is_success = FAIL;
            $msg = '삭제되지 않았습니다.';
        }

        echo response_to_json($msg, $is_success);
        exit;
    } //삭제 끝.

    // 등록전일때 삭제
    if ($w === 'delete_temp') {
        $is_auth = false;
        if ($is_admin === 'super') {
            $is_auth = true;
        }
        if (!$is_auth) {
            http_response_code(401);
            echo response_to_json('권한이 없습니다.', SUCCESS);
            exit;
        }

        $date = date_create(G5_TIME_YMDHIS);
        date_modify($date, '-1 day');
        $yesterday = date_format($date, 'Y-m-d H:i:s');

        $select_sql = "select file_name, save_time from {$g5['comment_file_table']} where comment_id is null and save_time < '{$yesterday}'";
        $result = sql_query($select_sql);
        if ($result) {
            $remove_files = array();
            $remove_original_images = array();
            while ($row = sql_fetch_array($result)) {
                $file_info = array('file_name' => $row['file_name'], 'save_time' => $row['save_time']);
                $remove_files[] = $file_info;
                $file_extension = pathinfo(strtolower($row['file_name']), PATHINFO_EXTENSION);

                if (strpos($row['file_name'], 'thumb-') !== false) {
                    $tmp = explode('thumb-', $row['file_name']);
                    $name = $tmp[1];
                    $file_name = explode('_', $name);
                    $file_name = $file_name[0] . '_' . $file_name[1] . '_' . $file_name[2] . '.' . $file_extension;
                    $remove_original_images[] = array('file_name' => $file_name, 'save_time' => $row['save_time']);
                }
            }


            run_event('remove_invalid_file', $remove_files, $remove_original_images);

            if (isset($remove_original_images) && count($remove_original_images) > 0) {
                foreach ($remove_original_images as $key) {
                    $get_save_month = date('ym', strtotime($key['save_time']));
                    $path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name'];
                    @unlink(COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name']);
                }
            }

            if (count($remove_files) > 0) {
                foreach ($remove_files as $key) {
                    $get_save_month = date('ym', strtotime($key['save_time']));
                    $path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name'];
                    $remove_result = @unlink(COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name']);
                }

                $delete_sql = "delete from {$g5['comment_file_table']} where comment_id is null and save_time < '{$yesterday}'";
                $result = sql_query($delete_sql);

                if ($result) {
                    echo response_to_json('완료되었습니다.', SUCCESS);
                    exit;
                }
            } else {
                echo response_to_json('삭제할 파일이 없습니다.', FAIL);
                exit;
            }
        }
    }

    if ($w === 'delete_check_late') {
        $is_auth = false;
        if ($is_admin === 'super') {
            $is_auth = true;
        }
        if (!$is_auth) {
            http_response_code(401);
            echo response_to_json('권한이 없습니다.', FAIL);
            exit;
        }

        $select_sql = "select file_name, save_time from {$g5['comment_file_table']} where is_delete = 1";
        $result = sql_query($select_sql);
        if ($result) {
            $remove_files = array();
            $remove_original_images = array();
            while ($row = sql_fetch_array($result)) {
                $file_info = array('file_name' => $row['file_name'], 'save_time' => $row['save_time']);
                $remove_files[] = $file_info;

                $file_extension = pathinfo(strtolower($row['file_name']), PATHINFO_EXTENSION);

                if (strpos($row['file_name'], 'thumb-') !== false) {
                    $tmp = explode('thumb-', $row['file_name']);
                    $name = $tmp[1];
                    $file_name = explode('_', $name);
                    $file_name = $file_name[0] . '_' . $file_name[1] . '_' . $file_name[2] . '.' . $file_extension;
                    $remove_original_images[] = array('file_name' => $file_name, 'save_time' => $row['save_time']);
                }
            }


            run_event('remove_check_late_file', $remove_files, $remove_original_images);

            if (isset($remove_original_images) && count($remove_original_images) > 0) {
                foreach ($remove_original_images as $key) {
                    $get_save_month = date('ym', strtotime($key['save_time']));
                    $path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name'];
                    @unlink(COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name']);
                }
            }

            if (count($remove_files) > 0) {
                foreach ($remove_files as $key) {
                    $get_save_month = date('ym', strtotime($key['save_time']));
                    $path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name'];
                    $remove_result = @unlink(COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $key['file_name']);
                }

                $delete_sql = "delete from {$g5['comment_file_table']} where is_delete = 1";
                $result = sql_query($delete_sql);

                if ($result) {
                    echo response_to_json('완료되었습니다.', SUCCESS);
                    exit;
                }
            } else {
                echo response_to_json('삭제할 파일이 없습니다.', SUCCESS);
                exit;
            }
        }
    }

    if ($w === 'download') {
        ob_end_clean();

        @include_once($board_skin_path . '/comment_download.skin.php'); //사용자 코드

        if ($member_level < $download_level) {
            http_response_code(401);
            echo response_to_json('권한이 없습니다.', FAIL);
            exit;
        }

        $select_sql = 'select file_name, file_download_count, file_source_name, save_time from ? where comment_id = ? and file_name = ? and bo_table = ?';

        $stmt = $link->prepare($select_sql);
        $stmt->bind_param('ssss', $g5['comment_file_table'], $comment_id, $file_name, $bo_table);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($stmt->affected_rows < 0) {
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
            $update_download_count_sql = 'update ' . $g5['comment_file_table'] . ' set file_download_count = ? where file_name ? and bo_table ? ';

            $stmt = $link->prepare($update_download_count_sql);
            $stmt->bind_param('iss', $file_download_count, $file_name, $bo_table);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $is_valid = true;
            }

            set_session($ss_name, true);
        }

        $file_path = COMMENT_FILE_PATH . "/{$get_save_month}/" . $file_name;
        $file_exist_check = file_exists($file_path);

        run_event('download_file_header', $fileinfo_row, $file_exist_check);

        if ($file_exist_check === false) {
            http_response_code(400);
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

        header("expires: 0");
        flush();

        $fp = fopen($file_path, 'rb');

        $download_rate = 10;

        while (!feof($fp)) {
            print fread($fp, (int)round($download_rate * 1024));
            flush();
            usleep(1000);
        }

        fclose($fp);
        flush();
    }
}

/**
 * 댓글 첨부 파일 업로더
 * @param $file
 * @return array
 */
function comment_file_uploader($file)
{
    $file_count = count($file['name']);
    if ($file_count === 0) {
        return array('is_success' => FAIL, 'msg' => '업로드 실패했습니다. 파일을 확인해주세요.');
    }
    $ym = date('ym', G5_SERVER_TIME);
    $comment_file_path = COMMENT_FILE_PATH . "/{$ym}/";

    if (!is_dir($comment_file_path)) {
        @mkdir($comment_file_path);
        @chmod($comment_file_path, G5_DIR_PERMISSION);
    }

    //응답배열
    $response = array();

    for ($i = 0; $i < $file_count; $i++) {
        if (strpos($file['name'][$i], '.php') !== false) {
            continue;
        }

        $file['name'][$i] = str_replace(' ', '_', trim($file['name'][$i]));
        $file_name_pathinfo = pathinfo(get_safe_filename(strtolower($file['name'][$i])));
        $file_extension = $file_name_pathinfo['extension'];

        if (preg_match('/(\.jpe?g|\.gif|\.png|\.webp|bmp)$/i', $file['name'][$i])) {
            $imageinfo = getimagesize($file['tmp_name'][$i]);
            if ($imageinfo === false) {
                if (!($file_extension === 'webp' && PHP_VERSION_ID < 70100)) {
                    $response = array(
                        'is_success' => FAIL,
                        'msg' => '잘못된 사진 파일입니다.'
                    );
                }
                return json_encode($response);
            }

            if (COMMENT_FILE_IMG_RESIZE == 0) {
                $thumb_width = $imageinfo[0];
                $thumb_height = $imageinfo[1];
            } else {
                $thumb_width = round($imageinfo[0] * (COMMENT_FILE_IMG_RESIZE / 100));
                $thumb_height = round($imageinfo[1] * (COMMENT_FILE_IMG_RESIZE / 100));
            }
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

            $new_file_name = file_name_generator($file['name'][$i]);
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
            @chmod("{$comment_file_path}/{$save_file_name}", G5_FILE_PERMISSION);
            $result = write_comment_file_info($file['name'][$i], $result_save_file, $file['size'][$i]);
            $comment_file_url = G5_DATA_URL . '/' . 'comment' . '/' . $ym;
            $end_point_url = run_replace('replace_url', $comment_file_url);
            $save_fileinfo = array(
                'original_name' => $file['name'][$i],
                'file_name' => $result_save_file,
                'file_type' => $file_extension,
                'end_point' => $end_point_url
            );
            if($result === false){
                $save_fileinfo['is_success'] = FAIL;
            }
            $response['files'][] = $save_fileinfo;
        }
    }

    return $response;
}

/**
 * 코맨트 DB에 파일정보기록
 * @param string $file_original_name
 * @param string $file_save_name
 * @return bool DB기록 성공/실패
 */
function write_comment_file_info($file_original_name, $file_save_name, $file_size)
{
    global $g5;

    $write_file_info_sql = 'INSERT INTO ' . $g5['comment_file_table'] . '  set file_source_name = ?, file_name = ?, save_time = ?, file_size = ?';
    /**
     * @var $stmt mysqli_stmt
     */
    $stmt = $GLOBALS['connect_db']->prepare($write_file_info_sql);
    $time = G5_TIME_YMDHIS;
    $stmt->bind_param('sssi', $file_original_name, $file_save_name, $time, $file_size);
    $result = $stmt->execute();
    if($result == false){
        return false;
    }

    return $stmt->affected_rows === 1;
}

function file_name_generator($file_name)
{
    $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    shuffle($chars_array);
    $shuffle = implode('', $chars_array);
    return sha1(microtime()) . '_' . substr($shuffle, 0, 8) . '_' . replace_file_name(get_safe_filename($file_name));
}

function replace_file_name($name): string
{
    $usec = get_microtime();
    $return_filename = sha1($_SERVER['REMOTE_ADDR'] . $usec);
    $file_path = pathinfo($name);
    $ext = $file_path['extension'];
    if ($ext) {
        $return_filename .= ".$ext";
    }
    return $return_filename;
}

function create_comment_file_table()
{
    global $g5;

    $create_table = "CREATE TABLE IF NOT EXISTS `{$g5['comment_file_table']}` (
      `file_id` int(11) NOT NULL AUTO_INCREMENT,
      `file_source_name` varchar(255) NOT NULL,
      `file_name` varchar(255) NOT NULL,
      `file_download_count` int(11) DEFAULT 0,
      `comment_id` int(11) DEFAULT NULL,
      `bo_table` varchar(11) DEFAULT NULL,
      `is_delete` tinyint(4) DEFAULT 0,
      `save_time` datetime NOT NULL,
      `file_size` int(11) NULL DEFAULT 0,
      PRIMARY KEY (`file_id`)
    ) ENGINE=INNODB DEFAULT CHARSET=utf8";

    $create_table = get_db_create_replace($create_table);
    sql_query($create_table, true);

    //index
    $create_table = "CREATE INDEX comment_id_idx ON `{$g5['comment_file_table']}` (comment_id)";
    @sql_query($create_table, true);
}

function add_comment_setting_columns()
{
    global $g5;

    sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_count` TINYINT NOT NULL DEFAULT '2' AFTER `bo_10` ", false);

    if (!isset($board['bo_cf_size_limit'])) {
        sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_size_limit` INT NOT NULL DEFAULT '3145728' AFTER `bo_cf_count` ", false);
    }

    if (!isset($board['bo_cf_upload_level'])) {
        sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_upload_level` TINYINT NOT NULL DEFAULT '1' AFTER `bo_cf_size_limit` ", false);
    }

    if (!isset($board['bo_cf_download_level'])) {
        sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_download_level` TINYINT NOT NULL DEFAULT '1' AFTER `bo_cf_upload_level` ", false);
    }

    if (!isset($board['bo_cf_is_late_delete'])) {
        sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_is_late_delete` TINYINT NOT NULL DEFAULT '0' AFTER `bo_cf_download_level` ", false);
    }

    if (!isset($board['bo_cf_resize'])) {
        sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_cf_resize` TINYINT NOT NULL DEFAULT '90' AFTER `bo_cf_is_late_delete` ", false);
    }
}

/**
 * 상태메시지를 설정하고 json을 응답하는 함수.
 * @param string $msg
 * @param bool $is_success
 * @return string json ['msg' => string , 'is_success' => bool]
 */
function response_to_json(string $msg, bool $is_success)
{
    $response = [
        'msg' => $msg,
        'is_success' => $is_success
    ];
    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

function file_delete($file_name, $get_save_month)
{
    $file_path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $file_name;
    $is_delete = @unlink($file_path);

    if (strpos($file_name, 'thumb-') !== false) {
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $tmp = explode('thumb-', $file_name);
        $name = $tmp[1];
        $file_name = explode('_', $name);
        $file_name = $file_name[0] . '_' . $file_name[1] . '_' . $file_name[2] . '.' . $file_extension;

        $file_path = COMMENT_FILE_PATH . '/' . $get_save_month . '/' . $file_name;
        $is_delete = @unlink($file_path);
    }

    return $is_delete;
}