<?php
/**
 * 구독서비스 Model Class
 */
class BillingServiceModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 구독서비스(상품) 1건 조회
     * @param int $serviceId    서비스(상품) ID
     * @return array|null
     */
    public function selectOneById($serviceId)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    bs.*,
                    b.bo_subject, b.bo_table
                FROM {$g5["billing_service_table"]} bs
                LEFT JOIN g5_board b ON bs.service_table = b.bo_table
                WHERE service_id = ?";
        array_push($bindParam, $serviceId);

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 구독서비스(상품) 목록 조회
     * @param array $requestData
     * @return array
     */
    public function selectList($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    bs.*,
                    b.bo_subject
                FROM {$g5["billing_service_table"]} bs 
                LEFT JOIN {$g5["board_table"]} b ON bs.service_table = b.bo_table
                WHERE 1=1";
        /* 검색조건 */
        if (!empty($requestData['is_use'])) {
            $sql .= " AND is_use = ? ";
            array_push($bindParam, $requestData['is_use']);
        }
        if (!empty($requestData['service_table'])) {
            $sql .= " AND service_table = ? ";
            array_push($bindParam, $requestData['service_table']);
        }
        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }
        /* 정렬 */
        if (!empty($requestData['sst'])) {
            $sql .= " ORDER BY `{$requestData['sst']}` {$requestData['sod']} ";
        }
        /* 반환 결과 수 */
        if (isset($requestData['offset'], $requestData['rows'])) {
            $sql .= " LIMIT ?, ?";
            array_push($bindParam, $requestData['offset'], $requestData['rows']);
        }

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 구독서비스(상품) 전체 갯수
     * @param array $requestData
     * @return int
     */
    public function selectTotalCount($requestData = array())
    {
        global $g5;
        
        $bindParam = array();

        $sql = "SELECT 
                    COUNT(*) AS cnt
                FROM {$g5["billing_service_table"]} bs
                LEFT JOIN g5_board b ON bs.service_table = b.bo_table
                WHERE 1=1 ";
        /* 검색조건 */
        if (!empty($requestData['is_use'])) {
            $sql .= " AND is_use = ? ";
            array_push($bindParam, $requestData['is_use']);
        }
        if (!empty($requestData['service_table'])) {
            $sql .= " AND service_table = ? ";
            array_push($bindParam, $requestData['service_table']);
        }
        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['cnt'];
    }

    /**
     * 구독서비스(상품) 등록
     * @param array $requestData
     * @return bool
     */
    public function insert($requestData = array())
    {
        global $g5;

        $data = array(
            'name' => $requestData['name'],
            'summary' => $requestData['summary'],
            'explan' => $requestData['explan'],
            'mobile_explan' => $requestData['mobile_explan'],
            'image_path' => $requestData['image_path'],
            'order' => $requestData['order'],
            'is_use' => $requestData['is_use'],
            'expiration' => $requestData['expiration'],
            'expiration_unit' => $requestData['expiration_unit'],
            'recurring' => $requestData['recurring'],
            'recurring_unit' => $requestData['recurring_unit'],
            'service_table' => $requestData['service_table'],
            'service_url' => $requestData['service_url'],
            'service_hook_code' => $requestData['service_hook_code'],
            'is_event' => $requestData['is_event'],
            'event_period' => $requestData['event_period'],
            'event_unit' => $requestData['event_unit'],
            'event_price' => $requestData['event_price']
        );

        return $this->g5Mysqli->insertSQL($g5["billing_service_table"], $data);
    }

    /**
     * 구독서비스(상품) 수정
     * @param int $serviceId        서비스(상품) ID
     * @param array $requestData
     * @return bool
     */
    public function update($serviceId, $requestData = array())
    {
        global $g5;

        $conditional = array(
            "service_id" => $serviceId
        );

        return $this->g5Mysqli->updateSQL($g5["billing_service_table"], $requestData, $conditional);
    }
}
