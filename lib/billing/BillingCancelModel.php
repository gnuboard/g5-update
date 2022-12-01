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
            "cancel_time"       => $resultData['cancel_time']
        );
        return $this->g5Mysqli->insertSQL($g5['billing_cancel_table'], $bindParam);
    }
}
