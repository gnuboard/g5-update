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
