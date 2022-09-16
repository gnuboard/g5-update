<?php

include_once('./_common.php');
include_once(G5_LIB_PATH . '/Nonce.php');  //NONCE
$is_editor_upload = false;
$nonce = Nonce::get_instance();

if ($nonce->ft_nonce_is_valid('editor_image_upload')) {
    $is_editor_upload = true;
}

$board = get_board_db($bo_table);

$ym = date('ym', G5_SERVER_TIME);
$upload_config = array(
    'upload_path' => G5_DATA_PATH . '/' . G5_EDITOR_DIR . '/' . $ym,
    'upload_url' => G5_DATA_URL . '/' . G5_EDITOR_DIR . '/' . $ym,
    'upload_size_limit' => $board['bo_cf_size_limit'],
    'upload_level' => $board['bo_upload_level'],
    'thumb_width' => $board['bo_image_width']
);

if($w == 'u') {
    if ($is_editor_upload === false || empty($board['bo_table']) || (count($_FILES['file']['name']) == 0)) {
        $response = array(
            'is_error' => true,
            'msg' => '잘못된 요청입니다.'
        );
        header('400 Bad Request', true, 400);
        echo json_encode($response);
        exit;
    }

    $member_level = $is_guest ? 0 : $member['mb_level']; //guest 는 0
    if (!($member_level >= $upload_config['upload_level'])) {
        $response = array(
            'is_error' => true,
            'msg' => '권한이 없습니다.'
        );
        header('401 Unauthorized', true, 401);
        echo json_encode($response);
        exit;
    }

    $delay_seconds = 2;
    if (isset($_SESSION['ss_datetime']) && ($_SESSION['ss_datetime'] >= (G5_SERVER_TIME - $delay_seconds))) {
        $response = array(
            'is_error' => true,
            'msg' => '너무 빠른 시간내에 연속해서 올릴 수 없습니다.'
        );

        header('429 Too Many Requests', true, 429);
        echo json_encode($response);
        exit;
    }

    $invalid_file_list = array();
    $file_count = count($_FILES['file']['size']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['file']['size'][$i] > (int)$upload_config['upload_size_limit']) {
            $invalid_file_list[] = $_FILES['file']['name'];
            unset(
                $_FILES['file']['name'][$i],
                $_FILES['file']['size'][$i],
                $_FILES['file']['full_path'][$i],
                $_FILES['file']['type'][$i],
                $_FILES['file']['tmp_name'][$i],
                $_FILES['file']['error'][$i]
            );
        }
    }


    $upload_result = file_uploader($_FILES['file'], $upload_config);

    if (isset($upload_result['is_error']) || count($invalid_file_list) > 0) {
        if (count($invalid_file_list)) {
            $allow_file_size = $upload_config['upload_size_limit'] > 0 ? $upload_config['upload_size_limit'] / 1024 / 1024 : 0;
            $upload_result['error_file_list'] = $invalid_file_list;
            $upload_result['is_error'] = true;
            $upload_result['allow_file_size'] = round($allow_file_size, 3);
        }

        header('400 Bad Request ', true, 400);
    }

    run_event('after_upload_file', $upload_result);

    set_session("ss_datetime", G5_SERVER_TIME);

    echo json_encode($upload_result);
    exit;
}


/**
 * 댓글 첨부 파일 업로더
 * @param $file
 * @return array
 */
function file_uploader($file, $config)
{
    $file_count = count($file['name']);
    if ($file_count === 0) {
        return array('is_error' => true, 'msg' => '업로드 실패했습니다. 파일을 확인해주세요.');
    }

    if (!is_dir($config['upload_path'])) {
        @mkdir($config['upload_path']);
        @chmod($config['upload_path'], G5_DIR_PERMISSION);
    }

    $response = array();
    for ($i = 0; $i < $file_count; $i++) {
        if (strpos($file['name'][$i], '.php') !== false) {
            continue;
        }

        $file['name'][$i] = str_replace(' ', '_', trim($file['name'][$i]));
        $file_name_pathinfo = pathinfo(get_safe_filename(strtolower($file['name'][$i])));
        $file_extension = $file_name_pathinfo['extension'];
        $is_success = false;
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

            $new_file_name = file_name_generator($file_name_pathinfo['filename']);
            $temp_file_name_pathinfo = pathinfo($file['tmp_name'][$i]);


            /**
             * @var $save_file_name string 저장될 파일이름
             */
            $save_file_name = str_replace(
                $file['tmp_name'][$i],
                $new_file_name . '.' . $file_name_pathinfo['extension'],
                $file['tmp_name'][$i]
            );

            if ($temp_file_name_pathinfo['basename']) { //원본
                $is_success = move_uploaded_file($file['tmp_name'][$i], "{$config['upload_path']}/$save_file_name");
                $result_save_file = $save_file_name;
            }
        }

        if ($is_success) {
            @chmod("{$config['upload_path']}/{$save_file_name}", G5_FILE_PERMISSION);
            $end_point_url = run_replace('replace_url', $config['upload_url']);
            $save_fileinfo = array(
                'original_name' => $file['name'][$i],
                'file_name' => $result_save_file,
                'file_type' => $file_extension,
                'end_point' => $end_point_url
            );
            $response['files'][] = $save_fileinfo;
        } else {
            $response['files'] = array();
        }
    }

    return $response;
}

function file_name_generator($file_name)
{
    $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    shuffle($chars_array);
    $shuffle = implode('', $chars_array);
    return sha1(microtime()) . '_' . substr($shuffle, 0, 8) . '_' . replace_filename(get_safe_filename($file_name));
}