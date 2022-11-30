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
     * 다음 결제일 계산
     * 
     * @param string    $startDate      최초 결제 시작일 ('Y-m-d')
     * @param string    $paymentDate    결제일 ('Y-m-d')
     * @param int       $recurring      반복주기
     * @param string    $recurringUnit  반복주기 단위 (y:년, m:월, w:주, d:일)
     * @return string|false
     */
    function nextPaymentDate($startDate, $paymentDate, $recurring, $recurringUnit)
    {
        switch (strtolower($recurringUnit)) {
            case 'y' :
                $time = "+{$recurring} years";
                return date('Y-m-d', strtotime($time, strtotime($paymentDate)));
                break;
            case 'm' :
                /**
                 * 월 결제일 경우 아래 경우를 고려해서 계산한다.
                 * 1. 다음 결제일이 존재하지 않는 날짜일 때, "+{$recurring} months"가 다음 결제월을 초과하게 된다.
                 *      Ex) 1/31 => 2/31 (2/31은 없는 날짜이다.)
                 *      date('Y-m-d', strtotime("+1 months", strtotime('2022-01-31'))) 는 '2022-03-03'로 계산된다.
                 */
                $startDay       = date('d', strtotime($startDate));
                $paymentDateYm  = date('Y-m', strtotime($paymentDate));
                $nextMonthByYm  = date('m', strtotime("+{$recurring} months", strtotime($paymentDateYm)));
                $nextMonth      = date('m', strtotime("+{$recurring} months", strtotime($paymentDate)));

                // 다음 결제월을 초과할 경우, 다음 결제월 마지막 일자로 처리한다.
                if ($nextMonthByYm != $nextMonth) {
                    return date('Y-m-t', strtotime("+{$recurring} months", strtotime($paymentDateYm)));
                // 매 월 동일한 일자로 처리되도록 고정한다.
                } else {
                    return date('Y-m-' . $startDay, strtotime("+{$recurring} months", strtotime($paymentDate)));
                }
                break;
            case 'w' :
                $recurring *= 7;
                $time = "+{$recurring} days";
                return date('Y-m-d', strtotime($time, strtotime($paymentDate)));
            case 'd' :
                $time = "+{$recurring} days";
                return date('Y-m-d', strtotime($time, strtotime($paymentDate)));
                break;
            default :
                return false;
                break;
        }
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
