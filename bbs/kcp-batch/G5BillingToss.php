<?php

include_once(dirname(__FILE__) . '/config.php');

class G5BillingToss implements BillingInterface
{
    public function __construct()
    {

    }

    /**
     * 빌링 키 발급 요청
     */
    public function requestIssueBillKey($data = array())
    {
        return "Toss requestIssueBillKey";
    }

    /**
     * 자동결제(빌링) 승인 요청
     */
    public function requestBilling($data = array())
    {

    }

    /**
     * 자동결제(빌링) 승인취소 요청
     */
    public function requestCancelBilling($no, $cancelReason = '')
    {

    }

    /**
     * 결제관련 API 요청
     */
    public function requestApi($url, $data)
    {

    }

    /**
     * PG사 데이터 -> 공용 데이터 변환
     */
    public function convertPgDataToCommonData()
    {
        
    }
}
