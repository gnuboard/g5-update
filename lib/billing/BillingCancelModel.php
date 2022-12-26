<?php
/**
 * 자동결제 취소 Model Class
 */
class BillingCancelModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 환불내역 조회
     * @param int $paymentNo    PG 결제번호
     * @return array
     */
    public function selectList($paymentNo)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    *
                FROM {$g5['billing_cancel_table']} 
                WHERE result_code = '0000'
                    AND payment_no = ?";
        array_push($bindParam, $paymentNo);

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 결제 건에 대한 환불가능금액 조회
     * @param string $paymentNo   주문번호
     * @return int
     */
    public function selectRefundableAmountByPaymentNo($paymentNo)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    refundable_amount
                FROM {$g5['billing_cancel_table']}
                WHERE type = 'partial'
                    AND result_code = '0000'
                    AND payment_no = ?
                ORDER BY id DESC
                LIMIT 1";
        array_push($bindParam, $paymentNo);

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['refundable_amount'];
    }

    /**
     * 결제에 대한 총 부분취소 금액 조회
     * 부분취소 한 적이없으면 0
     * @param string $paymentNo    PG 결제번호
     * @return int
     */
    public function selectTotalCancelAmount($paymentNo)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT
                    IFNULL(sum(cancel_amount), 0) as total_cancel_amount
                FROM {$g5['billing_cancel_table']}
                WHERE result_code = '0000'
                    AND payment_no = ?";
        array_push($bindParam, $paymentNo);

        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['total_cancel_amount'];
    }

    /**
     * 자동결제 취소 이력 저장
     * @param array $resultData     자동결제 취소요청 응답 데이터
     * @return int
     */
    public function insert($resultData = array())
    {
        global $g5;

        $bindParam = array(
            "od_id"             => $resultData['od_id'],
            "payment_no"        => $resultData['payment_no'],
            "type"              => isset($resultData['type']) ? $resultData['type'] : 'all',
            "result_code"       => $resultData['result_code'],
            "result_message"    => $resultData['result_message'],
            "cancel_no"         => isset($resultData['cancel_no']) ? $resultData['cancel_no'] : null,
            "cancel_reason"     => $resultData['cancel_reason'],
            "cancel_amount"     => $resultData['cancel_amount'],
            "refundable_amount" => isset($resultData['refundable_amount']) ? $resultData['refundable_amount'] : 0,
            "cancel_time"       => isset($resultData['cancel_time']) ? $resultData['cancel_time'] : date('Y-m-d H:i:s')
        );
        return $this->g5Mysqli->insertSQL($g5['billing_cancel_table'], $bindParam);
    }
}
