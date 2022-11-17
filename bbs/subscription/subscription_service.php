<?php

require_once(dirname(__FILE__, 3) . '/common.php');
//require_once(dirname(__FILE__, 1) . '/kcp-batch/G5Mysqli.php');

/**
 * @param $service_id
 * @return bool
 */
function checkAuth($service_id)
{
    $mb_id = $GLOBALS['mb_id'];
    if (empty($mb_id)) {
        return false;
    }

    $selectPaymentSql = 'select mb_id, expiration_date, payment_date from letf join '
        . G5_TABLE_PREFIX . 'batch_payment as batch_payment 
         ' . G5_TABLE_PREFIX . "batch_info as batch_info
        batch_info.od_id = batch_payment.od_id
        where batch_payment.service_id = $service_id and batch_payment.mb_id = $mb_id";

    $result = sql_query($selectPaymentSql);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $startDate = $row['payment_date'];
            $endDate = $row['expiration_date'];
        }

        $currentTime = strtotime(G5_TIME_YMDHIS);

        return $currentTime > strtotime($startDate) && $currentTime < strtotime($endDate);
    }

    return false;
}

/**
 * 게시판이름이나 url 확인후 인증함수 호출
 * @return void
 */
function checkRoute($url, $bo_table)
{
    $selectAllServiceRoutingSql = 'select bo_table, service_url, service_hook, service_id from ' . G5_TABLE_PREFIX . 'batch_service where service_use = 1';
    $result = sql_query($selectAllServiceRoutingSql);
    if ($result) {
        $path = parse_url($url, PHP_URL_PATH);
        $host = parse_url(G5_URL, PHP_URL_HOST);
        while ($row = sql_fetch_array($result)) {
            if ($row['bo_table'] === $bo_table) {
                $isAuth = checkAuth($row['service_id']);
                if ($isAuth === false) {
                    alert('결제가 필요합니다.', G5_BBS_URL . '/subscription/service_view?' . $row['service_id']);
                }
            } else {
                if (empty($row['service_url'])) {
                    continue;
                }

                if (parse_url($row['service_url'], PHP_URL_PATH) === $path
                    && parse_url($row['service_url'], PHP_URL_HOST) === $host) {
                    $isAuth = checkAuth($row['service_url']);
                    if ($isAuth === false) {
                        alert('결제가 필요합니다.', G5_BBS_URL . '/subscription/service_view?' . $row['service_id']);
                    }
                }
            }
        }
    }
}
