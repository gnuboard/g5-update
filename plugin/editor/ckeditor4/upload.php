<?php

include_once("../../../common.php");

// 업로드 경로 세팅
$ym = date('ym', G5_SERVER_TIME);
$data_dir = G5_DATA_PATH . '/editor/' . $ym;
$data_url = G5_DATA_URL . '/editor/' . $ym;
@mkdir($data_dir, G5_DIR_PERMISSION);
@chmod($data_dir, G5_DIR_PERMISSION);

// 업로드 DIALOG 에서 전송된 값
$funcNum = $_GET['CKEditorFuncNum'];

// 업로드 대상 파일
$upFile = $_FILES['upload'];
if (empty($upFile['tmp_name'])) {
    $msg = "파일이 존재하지 않습니다.";
    print_error($responseType, $msg);
}

$fileInfo = pathinfo($upFile['name']);
$filename = $fileInfo['filename'];
$extension = $fileInfo['extension'];
$extension = strtolower($extension);

if (!preg_match("/(jpe?g|gif|png|webp)$/i", $extension)) {
    $msg = "jpg / gif / png / webp 파일만 가능합니다.";
    print_error($responseType, $msg);
}
// jpeg 확장자 jpg로 통일되도록
if ($extension == 'jpeg') {
    $extension = 'jpg';
}

// 윈도우에서 한글파일명으로 업로드 되지 않는 오류 해결
$file_name = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) . '_' . get_microtime() . "." . $extension;
$save_dir = sprintf('%s/%s', $data_dir, $file_name);

if (move_uploaded_file($upFile["tmp_name"], $save_dir)) {
    // 썸네일 생성
    $img_width = $is_mobile ? 320 : 730;
    $tmp_thumb = img_thumbnail($save_dir, $img_width);
    $img_thumb = $tmp_thumb['src'];
    $save_url = sprintf('%s/%s', $data_url, $img_thumb);

    $res = run_replace('ckeditor_photo_upload', $save_dir, $save_url);

    // 성공 결과 출력
    if (strtolower($responseType) == "json") {
        $res = array();
        $res['fileName'] = $file_name;
        $res['url'] = $save_url;
        $res['uploaded'] = 1;
        $res['inserted'] = $ins;

        if ($file_name != $img_thumb) {  // 이름이 다르면 지정사이즈를 초과하여 썸네일화된것으로 간주, 출력 사이즈 지정
            $res['width'] = "100%";
            $res['height'] = "auto";
        }
        echo json_encode($res);
    } else {
        echo "<script>window.parent.CKEDITOR.tools.callFunction({$funcNum}, '{$save_url}', '');</script>";
    }
    exit;
}

$msg = "업로드 실패";
print_error($responseType, $msg);

function print_error($type, $msg)
{
    if (strtolower($type) == "json") {
        $res = array();
        $res['uploaded'] = 0;
        $res['error']['message'] = $msg;
        echo json_encode($res);
    } else {
        echo "<script> alert('{$msg}'); </script>";
    }
    exit;
}

// 업로드 썸네일 생성
function img_thumbnail($srcfile, $thumb_width = 0, $thumb_height = 0, $path = 0)
{
    $is_animated = false;   // animated GIF 체크
    if (is_file($srcfile)) {
        $filename = basename($srcfile);
        $filepath = dirname($srcfile);

        $size = @getimagesize($srcfile);
        $width = $size[0];  // 너비
        $height = $size[1]; // 높이

        // 원본 크기가 지정한 크기보다 크면 썸네일 생성진행
        if ($width > $thumb_width || ($thumb_height > 0 && $height > $thumb_height)) {
            // 원본비율에 맞게 너비/높이 계산
            $temp_width = $thumb_width;
            $temp_height = round(($temp_width * $height) / $width);
            // 계산된 높이가 지정된 높이보다 높을경우
            if ($thumb_height > 0 && $thumb_height < $temp_height) {
                $temp_height = $thumb_height;
                $temp_width = round(($temp_height * $width) / $height);
            }

            $thumb_filename = thumbnail($filename, $filepath, $filepath, $temp_width, $temp_height, false);
        }
        // 처리된 내용이 없으면 기존 파일 사용
        if (empty($thumb_filename)) {
            $thumb_filename = $filename;
        }


        switch ($path) {
            case 1 :
                $thumb_file = $filepath . "/" . $thumb_filename;
                $thumb_file = str_replace(G5_DATA_PATH, G5_DATA_URL, $thumb_file);
                break;
            default:
                $thumb_file = $thumb_filename;
                break;
        }
    }

    $res = array();
    $res['src'] = $thumb_file;
    $res['animated'] = $is_animated;

    return $res;
}
