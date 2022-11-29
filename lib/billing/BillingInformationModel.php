<?php
/**
 * 구독정보 Model Class
 */
class BillingInformationModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 자동결제 정보 1건 조회
     * @param string $orderId     주문번호
     * @return array|null
     */
    public function selectOneByOrderId($orderId)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    bi.*,
                    mb.mb_id, mb.mb_name, mb.mb_email,
                    bs.name, bs.recurring, bs.recurring_unit
                FROM {$g5['billing_information_table']} bi
                    LEFT JOIN {$g5['billing_service_table']} bs ON bi.service_id = bs.service_id
                    LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id
                WHERE od_id = ?";
        array_push($bindParam, $orderId);

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 자동결제 목록 조회
     * @param array $requestData
     * @return array
     */
    public function selectList($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    bi.od_id, bi.start_date, bi.end_date, bi.status, bi.service_id,
                    mb.mb_id, mb.mb_name, mb.mb_email,
                    bs.name, bs.recurring, bs.recurring_unit
                FROM {$g5['billing_information_table']} bi
                    LEFT JOIN {$g5['billing_service_table']} bs ON bi.service_id = bs.service_id
                    LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id
                WHERE 1=1";
        /* 검색조건 */
        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }
        if (!empty($requestData['status']) || $requestData['status'] === '0') {
            $sql .= " AND status = ?";
            array_push($bindParam, $requestData['status']);
        }
        if (!empty($requestData['mb_id'])) {
            $sql .= " AND mb_id = ?";
            array_push($bindParam, $requestData['mb_id']);
        }
        /* 정렬 */
        if (!empty($requestData['sst'])) {
            $sql .= " ORDER BY {$requestData['sst']} {$requestData['sod']} ";
        }
        /* 반환 결과 수 */
        if (!empty($requestData['offset']) && !empty($requestData['rows'])) {
            $sql .= " LIMIT ?, ?";
            array_push($bindParam, $requestData['offset'], $requestData['rows']);
        }
        
        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 자동결제 정보 전체 건수 조회
     * @param array $requestData
     * @return int
     */
    public function selectTotalCount($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    COUNT(*) AS cnt
                FROM {$g5['billing_information_table']} bi
                    LEFT JOIN {$g5['billing_service_table']} bs ON bi.service_id = bs.service_id
                    LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id
                WHERE 1=1";
        /* 검색조건 */
        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }
        if (!empty($requestData['status']) || $requestData['status'] === '0') {
            $sql .= " AND status = ?";
            array_push($bindParam, $requestData['status']);
        }
        if (!empty($requestData['mb_id'])) {
            $sql .= " AND mb_id = ?";
            array_push($bindParam, $requestData['mb_id']);
        }
        
        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['cnt'];
    }

    /**
     * 자동결제(빌링) 정보 저장
     */
    public function insertBillingInfo($requestData = array()) {
        
    }

    /**
     * 상태 값 업데이트
     * @param string $orderId   주문번호
     * @param string $billKey   자동결제(빌링) 키
     * @return int
     */
    public function updateStatus($orderId, $status)
    {
        global $g5;

        $bindParam      = array("status" => $status);
        $conditional    = array("od_id" => $orderId);

        return $this->g5Mysqli->updateSQL($g5['billing_information_table'], $bindParam, $conditional);
    }

    /**
     * 빌링 키 정보 업데이트
     * @param string $orderId   주문번호
     * @param string $billKey   자동결제(빌링) 키
     * @return int
     */
    public function updateBillingKey($orderId, $billingKey)
    {
        global $g5;

        $bindParam      = array("billing_key" => $billingKey);
        $conditional    = array("od_id" => $orderId);

        return $this->g5Mysqli->updateSQL($g5['billing_information_table'], $bindParam, $conditional);
    }

    /**
     * 다음 결제 예정일 업데이트
     * @param string $orderId   주문번호
     * @param string $date      결제 예정일
     * @param int
     */
    public function updateNextPaymentDate($orderId, $date)
    {
        global $g5;

        $bindParam      = array("od_id" => $orderId);
        $conditional    = array("next_payment_date" => $date);

        return $this->g5Mysqli->updateSQL($g5['billing_information_table'], $bindParam, $conditional);
    }
}
