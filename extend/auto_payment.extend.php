<?php
// 개별 페이지 접근 불가
if (!defined('_GNUBOARD_')) {
    exit;
}
/**
 * @todo 구독만료 처리 프로세스 실행 추가
 */


/**
 * 관리자페이지 > DB업그레이드 실행 Hook
 */
add_replace('admin_dbupgrade', 'add_billing_admin_dbupgrade', 10, 3);

/**
 * 자동결제 데이터베이스 업데이트
 * - 기존 DB업데이트와 별도로 표시하므로 paramter를 그대로 반환한다.
 * @param bool $is_check
 * @return bool
 */
function add_billing_admin_dbupgrade($is_check)
{
    $sql_path = G5_LIB_PATH . '/billing/_after_billing_db.sql';
    $result = true;
    $html = '';

    // 주석 및 접두사 변경
    $file = file_get_contents($sql_path);
    $file = preg_replace('/^--.*$/m', '', $file);
    $file = preg_replace('/`g5_([^`]+`)/', '`' . G5_TABLE_PREFIX . '$1', $file);

    $definition_list = explode(';', $file);

    foreach ($definition_list as $definition) {
        if (trim($definition) == '') {
            continue;
        }

        $sql = get_db_create_replace($definition);
        if (!sql_query($sql)) {
            $result = false;
        }
    }

    // 결과출력
    $html .= '<div class="local_desc01 local_desc">';
    $html .= '<p>';
    $html .= '자동결제 데이터베이스 업데이트 ' . ($result ? '성공' : '실패');
    $html .= '</p>';
    if (!$result) {
        $html .= '<p>';
        $html .= str_replace(G5_PATH, '', $sql_path) . ' 파일의 SQL문을 확인해주시기 바랍니다.';
        $html .= '</p>';
    }
    $html .= '</div>';

    echo $html;

    return $is_check;
}
