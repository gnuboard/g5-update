<?php
/**
 * 자동결제(빌링) 공통 Class
 * - 생성자에서 입력받은 PG사의 Class를 선언한다.
 * - 각각 PG Class는 BillingInterface를 사용해서 구현한다.
 */
class Billing
{
    /**
     * @var instance $pg        PG사 Instance
     */
    public $pg = null;

    /**
     * @var \G5Mysqli $mysqli
     */
    public $mysqli = null;

    /**
     * 각 PG사 Class Name : 'G5Billing' + $pgCode
     * - Ex : G5BillingKcp (KCP) / G5BillingToss (Toss)
     * @param string $pgCode    자동결제 PG사 ('kcp')
     */
    public function __construct($pgCode)
    {
        $this->mysqli = new G5Mysqli();

        try {
            if (!$pgCode) {
                throw new LogicException("PG사 Code값이 없습니다.");
            }

            $className = 'G5Billing' . ucfirst(strtolower($pgCode));
            if (class_exists($className, false)) {
                /* ReflectionClass를 통해 PG사 Class의 instance를 생성한다. */
                $ref = new ReflectionClass($className);
                $this->setPg($ref->newInstanceArgs());
            } else {
                throw new LogicException("PG사 자동결제 Class를 찾을 수 없습니다 : {$className}");
            }
        } catch (LogicException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * 빌링 키 발급 이력 저장
     * @param string $mb_id     빌링 키 발급된 회원ID
     * @param array $resultData 빌링 키 발급 요청 API 결과데이터
     * @return int
     */
    public function insertIssueBillKeyLog($memberId, $resultData = array())
    {
        global $g5;

        // 입력 데이터 재 선언 (순서)
        $bind_param = array(
            $memberId,
            $resultData['result_code'],
            $resultData['result_msg'],
            $resultData['card_code'],
            $resultData['card_name'],
            $resultData['bill_key']
        );
        $sql = "INSERT INTO {$g5["kcp_batch_key_log_table"]} SET 
                    mb_id       = ?,
                    res_cd      = ?,
                    res_msg     = ?,
                    card_cd     = ?,
                    card_name   = ?,
                    batch_key   = ?,
                    date        = now()";
        return $this->mysqli->execSQL($sql, $bind_param, true);
    }

    /**
     * 빌링 키 정보 업데이트
     * @param string $orderId   주문번호
     * @param string $billKey   자동결제(빌링) 키
     * @return int
     */
    public function updateBillKey($orderId, $billKey)
    {
        global $g5;

        $sql = "UPDATE {$g5['batch_info_table']} SET
                    batch_key = ? 
                WHERE od_id = ?";
        return $this->mysqli->execSQL($sql, array($billKey, $orderId), true);
    }

    /**
     * 자동결제(빌링) 정보 저장
     */
    public function insertBillingInfo($data = array()) {

    }

    /**
     * 자동결제(빌링) 이력 저장
     * @param string $memberId      회원ID
     * @param array $paymenInfo     자동결제 정보
     * @param array $resultData     자동결제 승인 요청 API 결과데이터
     * @return int
     */
    public function insertBillingLog($memberId, $paymentInfo, $resultData = array()) {

        global $g5;

        // 입력 데이터 재 선언        
        $bindParam = array(
            $resultData['od_id'],
            $memberId,
            $paymentInfo['batch_key'],
            $paymentInfo['payment_count'],
            $paymentInfo['amount'],
            $resultData['result_code'],
            $resultData['result_msg'],
            $resultData['billing_no'],
            $resultData['card_name'],
            json_encode($resultData),
            $paymentInfo['payment_date'],
            $paymentInfo['expiration_date']
        );
        // 자동결제 이력 저장
        $sql = "INSERT INTO {$g5['batch_payment_table']} SET 
                    od_id           = ?,
                    mb_id           = ?,
                    batch_key       = ?,
                    payment_count   = ?,
                    amount          = ?,
                    res_cd          = ?,
                    res_msg         = ?,
                    tno             = ?,
                    card_name       = ?,
                    res_data        = ?,
                    payment_date    = ?,
                    expiration_date = ?";
        return $this->mysqli->execSQL($sql, $bindParam, true);
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

        $sql = "UPDATE {$g5['batch_info_table']} SET
                    next_payment_date = ?
                WHERE od_id = ?";
        return $this->mysqli->execSQL($sql, array($date, $orderId), true);
    }

    /**
     * 자동결제(빌링) 환불 이력 저장
     */
    public function insertBillingRefund($data = array()) {

    }

    /**
     * PG사 요청 결과 데이터 이름 -> 공용 데이터 명 변환
     * - 각 PG Class의 convert 배열을 사용하여 변환한다.
     * @param array $pgData     변환할 이름 배열 (KCP변수(Key) => 공통변수(Value))
     * @return array
     */
    public function convertPgDataToCommonData($pgData = array())
    {
        $convert = $this->pg->convert;

        foreach ($pgData as $key => $val) {
            if (isset($convert[$key])) {
                unset($pgData[$key]);
                $pgData[$convert[$key]] = $val;
            }
        }

        return $pgData;
    }

    /**
     * 빌링 키 문자열 숨김표시
     * @param string $billKey       빌링 키
     * @param string $replacement   대체할 문자
     * @param string $offset        변환 시작위치
     * @param string $repeat        반복 횟수
     * @return string 
     */
    public function displayBillKey($billKey, $replacement = '*', $offset = 4, $repeat = 8)
    {
        return substr_replace($billKey, str_repeat($replacement, $repeat), $offset, $repeat);
    }

    /**
     * Get the value of pg
     */ 
    public function getPg()
    {
        return $this->pg;
    }

    /**
     * Set the value of pg
     *
     * @return  self
     */ 
    public function setPg($pg)
    {
        $this->pg = $pg;

        return $this;
    }
}
