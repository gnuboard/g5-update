<?php

/**
 * 구독 마이페이지에 필요함 함수모음
 * json이나 html 은 반환하지 않습니다.
 * 비동기 요청은 ajax.myapge.php 참고.
 */

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once(dirname(__FILE__) . '../../../common.php');
require_once(G5_BBS_PATH . '/kcp-batch/G5Mysqli.php');
require_once (G5_BBS_PATH . '/kcp-batch/KcpBatch.php');

$kcpBatch = new KcpBatch();

/**
 * 내 구독 목록
 * 조회성공시 배열, 조회실패시 false 반환
 * @return array|false
 */
function showMyServiceList () {
    $mb_id = getUserId();
    if($mb_id === false){
        return false;
    }
    $selectAllMyServiceSql = 'select * from ' . G5_TABLE_PREFIX . 'batch_info as info
    left join '. G5_TABLE_PREFIX . 'batch_service as service
    on info.service_id = service.service_id
    left join '. G5_TABLE_PREFIX . 'batch_service_price as price
    on price.service_id = service.service_id
    where mb_id =' . "'$mb_id" . "'";

    $result = sql_query($selectAllMyServiceSql);
    if($result){
        $responseResult = array();
        $currentTime = strtotime(G5_TIME_YMDHIS);
        while ($row = sql_fetch_array($result)) {
            $responseResult[] = $row;
        }
        return $responseResult;
    }
    return false;
}

function showMyServicePaymentHistory ($od_id) {
    $mb_id = getUserId();
    if($mb_id === false){
        return false;
    }
    $selectPaymentHistorySql = 'select * from ' . G5_TABLE_PREFIX . 'batch_payment 
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);

    $result = sql_query($selectPaymentHistorySql);
    $responseResult = array();

    while ($row = sql_fetch_array($result)) {
        $responseResult[] = $row;
    }


    return $responseResult;
}

/**
 * 구독 취소
 * @param string|int $od_id 구독서비스 주문번호
 * @return bool
 */
function cancelMyService ($od_id) {
    $mb_id = getUserId();
    if($mb_id === false){
        return false;
    }

    $selectPaymentSql = 'select batch_key from ' . G5_TABLE_PREFIX . 'batch_info 
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);

    $result = sql_query($selectPaymentSql);
    if(!$result) {
        return false;
    }

    $resultRow = array();
    while ($row = sql_fetch_array($result)) {
        $resultRow[] = $row;
    }

    $oldBatchKey = $resultRow[0]['batch_key'];

    /**
     * @var KcpBatch $kcpBatch
     */
    global $kcpBatch;

    $batchDelResult = json_decode($kcpBatch->deleteBatchKey($oldBatchKey), true);
    if($batchDelResult === false || !array_key_exists('res_cd', $batchDelResult)){
        return false;
    }

    if($batchDelResult['res_cd'] !== "0000") {
        return $batchDelResult;
    }

    $stateChangeSql = 'update ' . G5_TABLE_PREFIX . 'batch_info set status = 0
    where mb_id = "' . sql_real_escape_string($mb_id) .  '" and od_id = ' . sql_real_escape_string($od_id);
    $result = sql_query($stateChangeSql);
    if($result){
        return true;
    } else {
        return false;
    }

}

/**
 * 쿼리 실행 후 영향받은 행 갯수 가져오는 함수.
 * @return int|string
 */
function affectedRowCounter()
{
    if (PHP_VERSION_ID >= 50400 && G5_MYSQLI_USE) {
        $affected_row = mysqli_affected_rows($GLOBALS['g5']['link']);
    } else {
        $affected_row = mysql_affected_rows($GLOBALS['g5']['link']);
    }
    return $affected_row;
}

/**
 * 사용자아이디 가져오는 함수
 * @return false|string
 */
function getUserId()
{
    global $config, $is_guest, $is_admin;
    if($is_guest){
        return false;
    }

    if($is_admin === 'super'){
        return $config['cf_admin'];
    }

    $mb_id = get_session('mb_id');
    if(empty($mb_id)){
        return false;
    }

    return $mb_id;
}