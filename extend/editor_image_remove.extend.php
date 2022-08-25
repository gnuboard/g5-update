<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$before_content = "";
$edtor_debug = true;

add_event('write_update_before', 'set_before_write_content', 1, 3);
add_event('write_update_after', 'remove_editor_upload_image', 1);
add_event('write_update_after', 'remove_contents_image', 2, 3);
if ($edtor_debug) {
    add_event('write_update_after', 'exit_write_update', 3);
}
/**
 * 수정하기 전의 본문내용 선언
 */
function set_before_write_content($board, $wr_id, $w)
{
    global $before_content, $edtor_debug;

    $table = G5_TABLE_PREFIX . "write_" . $board['bo_table'];

    if ($edtor_debug) {
        echo "==========set_before_write_content==========<br><br>";
        echo "table : " . $table;
        echo "<br>";
        echo "wr_id : " . $wr_id;
        echo "<br>";
        echo "w : " . $w;
        echo "<br>";
    }

    if ($w == "u") {
        $write_info = get_write($table, $wr_id);

        if ($edtor_debug) {
            print_r($write_info);
            echo "<br>";
        }

        $before_content = $write_info['wr_content'];
    }
}

/**
 * 본문에 없는 추가 이미지 삭제
 * @since 22.08.25
 */
function remove_editor_upload_image()
{
    global $edtor_debug;

    if ($edtor_debug) {
        echo "==========remove_editor_upload_image==========<br><br>";
    }

    $addImage = $_POST['addImage'];
    $wr_content = $_POST['wr_content'];

    if (isset($addImage)) {
        foreach ($addImage as $image) {
            $tmp = explode("/", $image);
            $filename = trim(end($tmp));

            if (!strstr($wr_content, $filename)) {
                delete_editor_image_file($filename);
            }
        }
    }
}

/**
 * 본문에 없는 기존 이미지 삭제
 * @since 22.08.25
 */
function remove_contents_image($board, $wr_id, $w)
{
    global $before_content, $edtor_debug;

    $table = G5_TABLE_PREFIX . "write_" . $board['bo_table'];

    if ($edtor_debug) {
        echo "==========remove_contents_image==========<br><br>";
    }

    if ($w == "u") {
        // 게시글 원본 이미지 목록
        $before_image_list = get_image_from_text($before_content);

        // 저장할 게시글 본문
        $wr = get_write($table, $wr_id);

        if(isset($before_image_list)) {
            foreach ($before_image_list as $image) {
                if (!strstr($wr['wr_content'], $image)) {
                    delete_editor_image_file($image);
                }
            }
        }
        
    }
}

/**
 * 문자열에 포함된 이미지 목록 추출
 */
function get_image_from_text($content)
{
    global $edtor_debug;
    if ($edtor_debug) {
        echo "=====get_image_from_text=====<br>";
    }

    preg_match_all("/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/i", $content, $matches);
    
    if ($edtor_debug) {
        print_r($matches);
        echo "<br>";
    }
    return $matches[1];
}

/**
 * 이미지 파일 삭제
 */
function delete_editor_image_file($image_url)
{
    global $edtor_debug;

    $image_path = str_replace(G5_URL, G5_PATH, $image_url);

    @unlink($image_path);
    
    if ($edtor_debug) {
        echo "삭제파일 : " . $image_path . "<br>";
    }
}

/**
 * debug 모드일 때 redirect 방지
 */
function exit_write_update()
{
    exit;
}