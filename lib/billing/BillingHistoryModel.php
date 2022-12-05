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
                    CONCAT(DATE_FORMAT(payment_date, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(expiration_date, '%Y-%m-%d')) AS period,
                    (SELECT SUM(cancel_amount) FROM {$g5["billing_cancel_table"]} bc WHERE bc.payment_no = bh.payment_no AND bc.result_code = '0000') AS total_cancel
                FROM {$g5['billing_history_table']} bh
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
     * @param array $resultData     
     * @return bool
     */
    public function insert($resultData = array()) {

        global $g5;

        $data = array(
            'od_id' => $resultData['od_id'],
            'mb_id' => $resultData['mb_id'],
            'billing_key' => $resultData['billing_key'],
            'amount' => $resultData['amount'],
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
