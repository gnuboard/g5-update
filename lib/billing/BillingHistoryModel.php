<?php
/**
 * 자동결제 이력 Model Class
 */
class BillingHistoryModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 결제이력 1건 조회
     * @param int $id   결제이력 ID
     * @return array|null
     */
    public function selectOneById($id)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *
                FROM {$g5['billing_history_table']}
                WHERE id = ?";
        array_push($bindParam, $id);

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }
    /**
     * 결제이력 1건 조회
     * @param int $od_id   주문번호
     * @return array|null
     */
    public function selectOneByOdId($od_id)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *
                FROM {$g5['billing_history_table']}
                WHERE od_id = ?
                ORDER BY payment_count DESC
                LIMIT 1";
        $bindParam[] = $od_id;

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 마지막 결제 성공내역 1건
     * @param string $orderId
     * @param string $resultCode
     * @return array|null
     */
    public function selectOneLastSuccessByOdId($orderId, $resultCode)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *
                FROM {$g5['billing_history_table']}
                WHERE od_id = ? and result_code = ?
                ORDER BY payment_count DESC
                LIMIT 1";
        $bindParam[] = $orderId;
        $bindParam[] = $resultCode;

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 결제이력 조회
     * @param array $requestData
     * @return array
     */
    public function selectListByAdmin($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    bh.*,
                    mb.mb_name, mb.mb_email, 
                    CONCAT(DATE_FORMAT(payment_date, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(expiration_date, '%Y-%m-%d')) AS period,
                    (SELECT SUM(cancel_amount) FROM {$g5["billing_cancel_table"]} bc WHERE bc.payment_no = bh.payment_no AND bc.result_code = '0000') AS total_cancel
                FROM {$g5['billing_history_table']} bh
                    LEFT JOIN {$g5['member_table']} mb ON bh.mb_id = mb.mb_id
                WHERE 1 = 1";

        /* 검색조건 */
        if ((isset($requestData['date']) && !empty($requestData['date']))) {
            $sql .= ' AND DATE_FORMAT(payment_date, "%Y-%m-%d") = DATE_FORMAT(?, "%Y-%m-%d")';
            array_push($bindParam, $requestData['date']);
        }

        if ((isset($requestData['date_time']) && !empty($requestData['date_time']))) {
            $sql .= ' AND payment_date = ?';
            array_push($bindParam, $requestData['date_time']);
        }

        /* 반환 결과 수 */
        if (isset($requestData['offset'], $requestData['rows'])) {
            $sql .= " LIMIT ?, ?";
            array_push($bindParam, $requestData['offset'], $requestData['rows']);
        }

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 결제이력 조회
     * @param array $requestData
     * @return int
     */
    public function selectTotalCountByAdmin($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    COUNT(*) as cnt
                FROM {$g5['billing_history_table']} bh
                WHERE 1 = 1";

        /* 검색조건 */
        if ((isset($requestData['date']) && !empty($requestData['date']))) {
            $sql .= ' AND DATE_FORMAT(payment_date, "%Y-%m-%d") = DATE_FORMAT(?, "%Y-%m-%d")';
            array_push($bindParam, $requestData['date']);
        }

        if ((isset($requestData['date_time']) && !empty($requestData['date_time']))) {
            $sql .= ' AND payment_date = ?';
            array_push($bindParam, $requestData['date_time']);
        }

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['cnt'];
    }

    /**
     * 결제이력 조회
     * @param string    $orderId    주문번호
     * @param int       $offset     시작위치
     * @param int       $rows       출력 갯수
     * @return array
     */
    public function selectListByOrderId($orderId, $offset = null, $rows = null)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *,
                    CONCAT(DATE_FORMAT(payment_date, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(expiration_date, '%Y-%m-%d')) AS period,
                    (SELECT SUM(cancel_amount) FROM {$g5["billing_cancel_table"]} bc WHERE bc.payment_no = bh.payment_no AND bc.result_code = '0000') AS total_cancel
                FROM {$g5['billing_history_table']} bh
                WHERE od_id = ?
                ORDER BY payment_count DESC, payment_date DESC";
        $bindParam[] = $orderId;
        
        if (isset($offset) && isset($rows)) {
            $sql .= " LIMIT ?, ?";
            $bindParam[] = $offset;
            $bindParam[] = $rows;
        }

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 결제회차 조회
     * @param string $orderId   주문번호
     * @return int
     */
    public function selectPaymentCount($orderId)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    payment_count
                FROM {$g5['billing_history_table']}
                WHERE od_id = ?
                    AND result_code = '0000'
                ORDER BY payment_count DESC
                LIMIT 1";
        array_push($bindParam, $orderId);

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['payment_count'];
    }

    /**
     * 자동결제(빌링) 이력 저장
     * @param array $resultData     
     * @return bool
     */
    public function insert($resultData = array()) {

        global $g5;

        $data = array(
            'od_id' => $resultData['od_id'],
            'mb_id' => $resultData['mb_id'],
            'billing_key' => $resultData['billing_key'],
            'amount' => isset($resultData['amount']) ? $resultData['amount'] : 0,
            'result_code' => $resultData['result_code'],
            'result_message' => $resultData['result_message'],
            'result_data' => json_encode($resultData),
            'card_name' => $resultData['card_name'],
            'payment_count' => $resultData['payment_count'],
            'payment_no' => $resultData['payment_no'],
            'payment_date' => $resultData['payment_date'],
            'expiration_date' => $resultData['expiration_date'],
        );

        return $this->g5Mysqli->insertSQL($g5["billing_history_table"], $data);
    }
}
