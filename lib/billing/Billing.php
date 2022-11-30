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
    public $g5Mysqli = null;

    /**
     * 각 PG사 Class Name : 'G5Billing' + $pgCode
     * - Ex : G5BillingKcp (KCP) / G5BillingToss (Toss)
     * @param string $pgCode    자동결제 PG사 ('kcp')
     */
    public function __construct($pgCode)
    {
        $this->g5Mysqli = G5Mysqli::getInstance();

        try {
            if (!$pgCode) {
                throw new LogicException("PG사 Code값이 없습니다.");
            }
            $className = 'G5Billing' . ucfirst(strtolower($pgCode));
            /* ReflectionClass를 통해 PG사 Class의 instance를 생성한다. */
            $ref = new ReflectionClass($className);
            $this->setPg($ref->newInstanceArgs());
        } catch (ReflectionException $e) {
            echo $e->getMessage() . " - PG사 자동결제 Class를 찾을 수 없습니다.";
            exit;
        } catch (LogicException $e) {
            echo $e->getMessage();
            exit;
        }
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
