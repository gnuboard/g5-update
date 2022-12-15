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
     * @var array $unitArray    주기 단위 배열
     */
    public $unitArray = array(
        'default'   => array('y' => '년', 'm' => '월', 'w' => '주', 'd' => '일'),
        'period'    => array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일'),
        'prefix'    => array('y' => '연간', 'm' => '월', 'w' => '주', 'd' => '일')
    );

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
     * 환불금액 계산
     * - `일별금액 * 잔여일`
     * @param array $history    결제내역
     * @param int $base_price   구독상품 원가
     * @return int
     */
    function calcurateRefundAmount($history, $base_price) {

        $sTime = strtotime($history['payment_date']);       // 결제시작일
        $eTime = strtotime($history['expiration_date']);    // 유효기간 만료일
        $todayTime = strtotime(date('Y-m-d H:i:s'));        // 오늘
    
        // 만료일이 지났을 경우 
        if ($todayTime > strtotime($history['expiration_date'])) {
            return 0;
        }
        $diff_day   = ceil(($todayTime - $sTime) / (60 * 60 * 24)); // 사용 일수
        $exp_day    = floor(($eTime - $sTime) / (60 * 60 * 24));    // 유효기간 일수
    
        // 결제금액 - (사용일수 * (기본가격 / 유효일수))
        return $history['amount'] - $diff_day * round($base_price / $exp_day);
    }

    /**
     * 주기 단위를 문자로 변환
     * @param string $string    입력 문자열
     * @param string $type      단위 형식 (default, period, prefix)
     * @return string
     */
    function convertDateUnitToText($string, $type = 'default')
    {
        if (isset($this->unitArray[$type])) {
            $string = strtr($string, $this->unitArray[$type]);
        }

        return $string;
    }

    /**
     * 주기 단위를 기간 형식에 맞게 텍스트 표시
     * @param array $service    구독상품 정보
     * @return string|false
     */
    function displayRecurring($service = array(), $type = 'period')
    {
        if (isset($service['recurring']) && (int)$service['recurring'] > 0) {
            return $service['recurring'] . $this->convertDateUnitToText($service['recurring_unit'], $type);
        } else {
            return false;
        }
    }

    /**
     * 주기 단위를 구독만료
     * @param array $service    구독상품 정보
     * @return string|false
     */
    function displayExpiration($service = array(), $type = 'period')
    {
        if (isset($service['expiration']) && (int)$service['expiration'] > 0) {
            return $service['expiration'] . $this->convertDateUnitToText($service['expiration_unit'], $type);
        } else {
            return false;
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

    /**
     * Get the value of unitArray
     * @param $type     단위 형식 (default, period, prefix)
     * @return array
     */ 
    public function getUnitArray($type = 'default')
    {
        if (isset($this->unitArray[$type])) {
            return $this->unitArray[$type];
        } else {
            return $this->unitArray['default'];
        }
    }

    /**
     * 자동결제 가격 조회
     * - 결제종료일의 존재유무에 따라 가져오는 가격이 달라진다.
     * @param string $orderId   주문번호
     * @param array $info       자동결제 정보
     * @return int
     */
    function getBillingPrice($orderId, $info = array())
    {
        $price = 0;

        if (empty($info) || !isset($info['price'])) {
            $info_model = new BillingInformationModel();
            $info = $info_model->selectOneByOrderId($orderId);
        }

        // 결제종료일이 있을 때, 결제정보 가격 조회
        if ($info['end_date'] != '0000-00-00 00:00:00' && !is_null($info['end_date'])) {
            $price = $info['price'];
        // 결제종료일이 없을 때, 구독상품 가격 조회
        } else {
            $price_model = new BillingServicePriceModel();
            $price = $price_model->selectCurrentPrice($info['service_id']);
        }

        return (int)$price;
    }
}
