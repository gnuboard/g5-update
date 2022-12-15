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
                    mb.mb_name, mb.mb_email, mb.mb_hp,
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
    public function selectList($requestData)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    bi.od_id, bi.start_date, bi.end_date, bi.status, bi.service_id, bi.next_payment_date,bi.billing_key,
                    mb.mb_id, mb.mb_name, mb.mb_email, mb.mb_hp,
                    bs.name, bs.recurring, bs.recurring_unit
                FROM {$g5['billing_information_table']} bi
                    LEFT JOIN {$g5['billing_service_table']} bs ON bi.service_id = bs.service_id
                    LEFT JOIN {$g5['member_table']} mb ON bi.mb_id = mb.mb_id
                WHERE 1=1";
        /* 검색조건 */
        if (isset($requestData['mb_id']) && !empty($requestData['mb_id'])) {
            $sql .= " AND bi.mb_id = ?";
            array_push($bindParam, $requestData['mb_id']);
        }

        if (($requestData['status']) == 1 || $requestData['status'] == 0) {
            $sql .= " AND status = ?";
            array_push($bindParam, $requestData['status']);
        }

        if (isset($requestData['date']) && !empty($requestData['date'])) {
            $sql .= ' AND date_format(bi.next_payment_date, "%Y-%m-%d") = ? ';
            array_push($bindParam, "{$requestData['date']}");
        }

        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }

        /* 정렬 */
        if (!empty($requestData['sst'])) {
            $sql .= " ORDER BY {$requestData['sst']} {$requestData['sod']} ";
        }
        /* 반환 결과 수 */
        if (isset($requestData['offset'], $requestData['rows'])) {
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
        if (!empty($requestData['status']) || $requestData['status'] === '0') {
            $sql .= " AND status = ?";
            array_push($bindParam, $requestData['status']);
        }

        if (isset($requestData['date']) && !empty($requestData['date'])) {
            $sql .= ' AND date_format(bi.next_payment_date, "%Y-%m-%d") = ? ';
            array_push($bindParam, "{$requestData['date']}");
        }

        if (!empty($requestData['mb_id'])) {
            $sql .= " AND mb_id = ?";
            array_push($bindParam, $requestData['mb_id']);
        }

        if (!empty($requestData['sfl']) && !empty($requestData['stx'])) {
            $sql .= " AND {$requestData['sfl']} LIKE ? ";
            array_push($bindParam, "%{$requestData['stx']}%");
        }

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['cnt'];
    }

    /**
     * 자동결제(빌링) 정보 저장
     * @param array $resultData     
     * @return bool
     */
    public function insert($requestData = array())
    {
        global $g5;

        $data = array(
            'od_id'             => $requestData['od_id'],
            'service_id'        => $requestData['service_id'],
            'mb_id'             => $requestData['mb_id'],
            'price'             => isset($requestData['price']) ? $requestData['price'] : 0,
            'billing_key'       => $requestData['billing_key'],
            'start_date'        => $requestData['start_date'],
            'end_date'          => $requestData['end_date'],
            'next_payment_date' => $requestData['next_payment_date']
        );

        return $this->g5Mysqli->insertSQL($g5["billing_information_table"], $data);
    }

    /**
     * 자동결제(빌링) 정보 수정
     * @param int   $orderId        결제정보 ID
     * @param array $requestData
     * @return bool
     */
    public function update($orderId, $requestData = array())
    {
        global $g5;

        $conditional = array(
            "od_id" => $orderId
        );

        return $this->g5Mysqli->updateSQL($g5["billing_information_table"], $requestData, $conditional);
    }

    /**
     * 상태 값 업데이트
     * @param string $orderId   주문번호
     * @param string $billKey   자동결제(빌링) 키
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function updateNextPaymentDate($orderId, $date)
    {
        global $g5;

        $bindParam      = array("next_payment_date" => $date);
        $conditional    = array("od_id" => $orderId);

        return $this->g5Mysqli->updateSQL($g5['billing_information_table'], $bindParam, $conditional);
    }

    /**
     * 구독서비스 권한 체크
     * @param string $memberId  회원 ID
     * @param int $serviceId    구독서비스 ID
     * @return bool 
     */
    public function checkPermission($memberId, $serviceId)
    {
        global $g5;

        $bindParam = array();

        if (empty($memberId) || empty($serviceId)) {
            return false;
        }

        $sql = "SELECT EXISTS(
                    SELECT
                        bh.id
                    FROM {$g5['billing_history_table']} bh
                        LEFT JOIN {$g5['billing_information_table']} bi ON bh.od_id = bi.od_id
                    WHERE bh.mb_id = ?
                        AND bi.service_id = ?
                        AND NOW() BETWEEN payment_date AND expiration_date
                        AND status = 1) as permission";
        array_push($bindParam, $memberId, $serviceId);

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        if ((int)$result['permission'] > 0) {
            return true;
        } else {
            return false;
        }
    }
}
