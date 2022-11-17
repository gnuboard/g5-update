<?php
Interface BillingInterface
{
    public function __construct();
    /**
     * 빌링 키 발급 요청
     */
    public function requestIssueBillKey($data = array());

    /**
     * 자동결제(빌링) 승인 요청
     */
    public function requestBilling($data = array());

    /**
     * 자동결제(빌링) 승인취소 요청
     */
    public function requestCancelBilling($no, $cancelReason = '');

    /**
     * 결제관련 API 요청
     */
    public function requestApi($url, $data);
}