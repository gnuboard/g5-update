<?php
/**
 * 빌링키 발급 이력 Model Class
 */
class BillingKeyHistoryModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 빌링 키 발급 이력 저장
     * @param array $resultData 빌링 키 발급 요청 API 결과데이터
     * @return int
     */
    public function insert($resultData = array())
    {
        global $g5;

        $bindParam = array(
            "pg_code"       => $resultData['pg_code'],
            "od_id"         => $resultData['od_id'],
            "mb_id"         => $resultData['mb_id'],
            "result_code"   => $resultData['result_code'],
            "result_message" => $resultData['result_message'],
            "card_code"     => $resultData['card_code'],
            "card_name"     => $resultData['card_name'],
            "card_no"       => $resultData['card_no'],
            "billing_key"   => $resultData['billing_key'],
            "issue_date"    => date('Y-m-d H:i:s'),
        );
        return $this->g5Mysqli->insertSQL($g5['billing_key_history_table'], $bindParam);
    }
}
