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
    where service.service_id = ' . sql_real_escape_string($serviceId) . ' and "' . G5_TIME_YMDHIS .'" > price_table.apply_date 
    order by price_table.apply_date desc limit 1';

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
function showServiceList($bo_table = '')
{
    $where = ' WHERE 1=1';
    if ($bo_table != '') {
        $where .= " AND bs.bo_table = '{$bo_table}'";
    }
    $sql = "SELECT 
                bs.*,
                board.bo_subject,
                (SELECT price FROM g5_batch_service_price sp WHERE bs.service_id = sp.service_id AND sp.apply_date <= NOW() ORDER BY apply_date DESC LIMIT 1) AS price
            FROM g5_batch_service bs
            LEFT JOIN g5_board board ON bs.bo_table = board.bo_table
            {$where}
            ORDER BY service_order ASC";
    $result = sql_query($sql);
    
    $responseItem = array();
    if ($result->num_rows > 0) {
        while ($row = sql_fetch_array($result)) {
            $responseItem[$row['bo_table']]['subject']      = $row['bo_subject'];
            $responseItem[$row['bo_table']]['service'][]    = $row;
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
    global $member;

    $mb_id = $member['mb_id'];
    if (empty($mb_id)) {
        return false;
    }

    $sql = "SELECT EXISTS(
                SELECT bp.id
                FROM
                    g5_batch_payment bp
                LEFT JOIN g5_batch_info bi ON bp.od_id = bi.od_id
                WHERE
                    bi.service_id = {$service_id}
                    AND bp.mb_id = '{$mb_id}'
                    AND now() BETWEEN payment_date AND expiration_date) as auth";
    $result = sql_fetch($sql);
    if ((int)$result['auth'] > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 게시판이름이나 url 확인후 인증함수 호출
 * @return void
 */
function checkRoute()
{
    $bo_table = $_GET['bo_table'];
    $check = false;

    $sql = "SELECT bo_table, service_url, service_hook, service_id FROM " . G5_TABLE_PREFIX . "batch_service WHERE service_use = 1 AND bo_table = '{$bo_table}'";
    $result = sql_query($sql);
    if ($result->num_rows > 0) {
        /*
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $host = parse_url(G5_URL, PHP_URL_HOST);
        */
        while ($row = sql_fetch_array($result)) {
            if ($row['bo_table'] === $bo_table) {
                $isAuth = checkAuth($row['service_id']);
                if ($isAuth) {
                    $check = true;
                }
            }
            /*
            else {
                if (empty($row['service_url'])) {
                    continue;
                }

                if (parse_url($row['service_url'], PHP_URL_PATH) === $path
                    && parse_url($row['service_url'], PHP_URL_HOST) === $host
                ) {
                    $isAuth = checkAuth($row['service_url']);
                    if ($isAuth === false) {
                        alert('결제가 필요합니다.2', G5_URL . '/skin/subscription/basic/service.skin.php?bo_table=' . $bo_table);
                    }
                }
            }
            */
        }
        if ($check === false) {
            alert('결제가 필요합니다.', G5_URL . '/skin/subscription/basic/service.skin.php?bo_table=' . $bo_table);
        }
    }
}
