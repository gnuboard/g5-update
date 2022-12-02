<?php

require_once(dirname(__FILE__) . '../../../common.php');
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

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
       service_table,
       name,
       summary,
       explan,
       mobile_explan,
       image_path,
       service_url,
       service_hook_code,
       expiration,
       expiration_unit,
       `order`,
       is_use,
       recurring,
       recurring_unit,
       price,
       price_table.application_date as price_apply_date
    from ' . G5_TABLE_PREFIX . 'billing_service as service
    left join ' . G5_TABLE_PREFIX . 'billing_service_price as price_table
        on service.service_id = price_table.service_id
    where service.service_id = ' . sql_real_escape_string($serviceId) . ' and "' . G5_TIME_YMDHIS .'" > price_table.application_date 
    order by price_table.application_date desc limit 1';

    $result = sql_query($selectByIdSql);
    $responseItem = array();

    while ($row = sql_fetch_array($result)) {
        $responseItem[] = $row;
    }


    return $responseItem;
}

/**
 * 모든 구독 서비스 목록
 * @param int $page 몇 쪽 1쪽 부터 시작.
 * @param int $page_rows 몇개씩 보여줄지
 * @return array 결과 없으면 빈배열 리턴
 */
function showServiceList($page, $page_rows, $bo_table = '')
{
    $where = ' WHERE 1=1';
    if ($bo_table != '') {
        $where .= " AND bs.bo_table = '{$bo_table}'";
    }
    $sql = "SELECT 
                bs.*,
                board.bo_subject,
                (SELECT price FROM g5_batch_service_price sp WHERE bs.service_id = sp.service_id AND sp.application_date <= NOW() ORDER BY application_date DESC LIMIT 1) AS price
            FROM g5_batch_service bs
            LEFT JOIN g5_board board ON bs.bo_table = board.bo_table
            {$where}
            ORDER BY order ASC";
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
