<?php
//네임스페이스는 제공자이름\실제사용할구분이름 입니다.

namespace Merry\plugin\CommentFileUploader;

/**
 * DB에 댓글과 연결된 이미지파일정보를 기록, 수정합니다.
 * 코맨트 입력, 업데이트후 write_comment_update.skin 에서 사용합니다.
 * @param $upload_file_list
 * @param $comment_id
 * @return void
 */
function update_comment_file_db($upload_file_list, $bo_table, $comment_id)
{
    $upload_file_list = explode(',', $upload_file_list);
    if (count($upload_file_list) > 0) {
        foreach ($upload_file_list as $file_name) {
            update_fileinfo($bo_table, $comment_id, $file_name);
        }
    }
}

/**
 * 파일 정보 기록
 * @param $bo_table
 * @param $comment
 * @param $comment_id
 * @param $file_name
 * @return void
 */
function update_fileinfo($bo_table, $comment_id, $file_name)
{
    $write_file_info_sql = 'UPDATE ' . G5_TABLE_PREFIX . 'comment_file SET comment_id = ?, bo_table = ? WHERE comment_id IS null AND file_name = ? ';
    /**
     * @var $stmt mysqli_stmt
     */
    $stmt = $GLOBALS['connect_db']->prepare($write_file_info_sql);
    $stmt->bind_param('iss', $comment_id, $bo_table, $file_name);
    $stmt->execute();
}

/**
 * 이미지 태그를 첨부파일 형식으로 변경
 * <img src='url'> -> [url]
 * @param string $content
 * @return array|string|string[]|null
 */
function change_img_tag_comment_file($content)
{
    return preg_replace_callback(
        '/<img[^>]* src=\"([^\"]*)\"[^>]*>/iS',
        'Merry\plugin\CommentFileUploader\filter_comment_save_url',
        $content
    );
}

/**
 * 코멘트 이미지 저장 url을 반환
 * @param $content
 * @return string|void
 */
function filter_comment_save_url($content)
{
    if (isset($content[1]) && !empty($content[1])) {
        $url = clean_xss_attributes($content[1]);
        if (strpos($url, G5_DATA_DIR . '/' . 'comment')) {
            return "[$url]";
        }
    }
}