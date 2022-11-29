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
     * 결제이력 조회
     * @param string $orderId   주문번호
     * @return array
     */
    public function selectListByOrderId($orderId)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *,
                    CONCAT(DATE_FORMAT(payment_date, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(expiration_date, '%Y-%m-%d')) AS period
                FROM {$g5['billing_history_table']}
                WHERE od_id = ?
                ORDER BY payment_count DESC, payment_date DESC";
        array_push($bindParam, $orderId);

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
     * @param array $requestData    자동결제 승인 요청 API 결과데이터
     * @return int
     */
    public function insert($resultData = array())
    {
        global $g5;
    }
}
