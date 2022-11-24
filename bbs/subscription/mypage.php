<?php

/**
 * 구독 마이페이지에 필요함 함수모음
 * json이나 html 은 반환하지 않습니다.
 * 비동기 요청은 ajax.myapge.php 참고.
 */

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once(dirname(__FILE__) . '../../../common.php');
require_once(G5_BBS_PATH . '/kcp-batch/G5Mysqli.php');
require_once(G5_BBS_PATH . '/kcp-batch/KcpBatch.php');

$kcpBatch = new KcpBatch();

/**
 * 내 구독 목록
 * 조회성공시 배열, 조회실패시 false 반환
 * @return array|false
 */
function get_myservice($status = 1)
{
    global $g5;

    $status = (int)$status;
    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $sql = "SELECT 
        *,
        board.bo_subject,
        (SELECT price FROM {$g5['batch_service_price_table']} sp WHERE service.service_id = sp.service_id AND sp.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS price
    FROM {$g5['batch_info_table']} AS info
    LEFT JOIN {$g5['batch_service_table']} AS service ON info.service_id = service.service_id
    LEFT JOIN g5_board board ON service.bo_table = board.bo_table
    WHERE mb_id ='{$mb_id}' AND status = {$status}";

    $result = sql_query($sql);
    if ($result) {
        $response = array();
        while ($row = sql_fetch_array($result)) {
            $response[$row['bo_table']]['subject'] = $row['bo_subject'];
            $response[$row['bo_table']]['service'][] = $row;
        }
        return $response;
    }
    return false;
}

/**
 * 내 구독 서비스 결제정보
 * @param $od_id
 * @return array|false
 */
function get_myservice_payments($od_id)
{
    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }
    $select_payment_history_sql = 'select * from ' . G5_TABLE_PREFIX . 'batch_payment 
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);

    $result = sql_query($select_payment_history_sql);
    $response = array();

    while ($row = sql_fetch_array($result)) {
        $response[] = $row;
    }

    return $response;
}

/**
 * 구독 취소
 * @param string|int $od_id 구독서비스 주문번호
 * @return bool
 */
function cancel_myservice($od_id)
{
    $mb_id = get_user_id();
    if ($mb_id === false) {
        return false;
    }

    $select_payment_sql = 'select batch_key from ' . G5_TABLE_PREFIX . 'batch_info 
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);

    $result = sql_query($select_payment_sql);
    if (!$result) {
        return false;
    }

    $result_row = array();
    while ($row = sql_fetch_array($result)) {
        $result_row[] = $row;
    }

    $old_batchkey = $result_row[0]['batch_key'];

    /**
     * @var KcpBatch $kcpBatch
     */
    global $kcpBatch;
    /*
    임시 주석처리
    $batchDelResult = json_decode($kcpBatch->deleteBatchKey($old_batchkey), true);
    if ($batchDelResult === false || !array_key_exists('res_cd', $batchDelResult)) {
        return false;
    }

    if ($batchDelResult['res_cd'] !== "0000") {
        return $batchDelResult;
    }
    */

    $state_change_sql = 'update ' . G5_TABLE_PREFIX . 'batch_info set status = 0
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);
    $result = sql_query($state_change_sql);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

/**
 * 쿼리 실행 후 영향받은 행 갯수 가져오는 함수.
 * @return int|string
 */
function get_affected_rows()
{
    if (PHP_VERSION_ID >= 50400 && G5_MYSQLI_USE) {
        $affected_row = mysqli_affected_rows($GLOBALS['g5']['connect_db']);
    } else {
        $affected_row = mysql_affected_rows($GLOBALS['g5']['connect_db']);
    }
    return $affected_row;
}

/**
 * 사용자아이디 가져오는 함수
 * @return false|string
 */
function get_user_id()
{
    global $config, $is_guest, $is_admin;
    if ($is_guest) {
        return false;
    }

    if ($is_admin === 'super') {
        return $config['cf_admin'];
    }

    /**
     * @todo 안되는 조건찾기
     */
    $mb_id = get_session('mb_id');
    if (empty($mb_id)) {
        return false;
    }

    return $mb_id;
}
