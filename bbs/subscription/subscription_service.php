<?php

require_once(dirname(__FILE__) . '../../../common.php');
//require_once(dirname(__FILE__) . '/kcp-batch/G5Mysqli.php');


/**
 * 서비스 선택 후 상세 보기
 * @return array
 * @var int $serviceId
 * @return array 결과 없으면 빈배열 리턴
 */
function showServiceDetail($serviceId)
{
    $selectByIdSql = 'select
       service.service_id,
       bo_table,
       service_name,
       service_summary,
       service_explan,
       service_mobile_explan,
       service_image,
       service_url,
       service_hook,
       service_expiration,
       service_expiration_unit,
       service_order,
       service_use,
       recurring_count,
       recurring_unit,
       price,
       price_table.apply_date as price_apply_date
    from ' . G5_TABLE_PREFIX . 'batch_service as service
    left join ' . G5_TABLE_PREFIX . 'batch_service_price as price_table
        on service.service_id = price_table.service_id
    where service.service_id = ' . sql_real_escape_string($serviceId);

    $result = sql_query($selectByIdSql);
    $responseItem = array();

        while ($row = sql_fetch_array($result)) {
            $responseItem[] = $row;
        }


    return $responseItem;
}

/**
 * 모든 구독 서비스 목록
 * @param int $pageNo 몇 쪽 0쪽 부터 시작.
 * @param int $pagePerCount 몇개씩 보여줄지
 * @return array 결과 없으면 빈배열 리턴
 */
function showServiceList($pageNo, $pagePerCount)
{
    $startPage = $pageNo * $pagePerCount;
    $lastPage = $startPage + $pagePerCount;

    ++$startPage; //예) 1p 일 때   11, 20
    if ($pageNo == 0) {
        $startPage = 0;
    }

    if ($pageNo < 0 || $lastPage < 0) {
        $startPage = 0;
        $lastPage = 0;
    }

    $selectAllServiceSql = 'select
    service.service_id,
    bo_table,
    service_name,
    service_summary,
    service_explan,
    service_mobile_explan,
    service_image,
    service_url,
    service_hook,
    service_expiration,
    service_expiration_unit,
    service_order,
    service_use,
    recurring_count,
    recurring_unit,
    price,
    price_table.apply_date as price_apply_date
    from ' . G5_TABLE_PREFIX . 'batch_service as service
    left join ' . G5_TABLE_PREFIX . 'batch_service_price as price_table
        on service.service_id = price_table.service_id order by service_order asc limit '
        . sql_real_escape_string($startPage) . ', ' . sql_real_escape_string($lastPage);

    $result = sql_query($selectAllServiceSql);
    $responseItem = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $responseItem[] = $row;
        }
    }

    return $responseItem;
}

/**
 * 게시판, url 등 진입 시 권한 체크
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