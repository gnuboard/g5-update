<?php
Interface BillingInterface
{
    public function __construct();
    /**
     * 빌링 키 발급 요청
     * @param array $data   Request Data
     */
    public function requestIssueBillKey($data = array());

    /**
     * 자동결제(빌링) 승인 요청
     * @param array $data   Request Data
     */
    public function requestBilling($data = array());

    /**
     * 자동결제(빌링) 승인취소 요청
     * @param string $no            PG사 거래번호
     * @param string $cancelReason  결제취소 사유
     */
    public function requestCancelBilling($no, $cancelReason = '');

    /**
     * 자동결제(빌링) 승인 부분취소 요청
     * @param string $no            PG사 거래번호
     * @param string $cancelReason  결제취소 사유
     * @param string $price         부분취소금액
     * @param string $remainingAmount   부분취소 후 남은 원금액 (입력하지 않아도 되는 PG사도 있으므로 연동문서 확인 할 것)
     */
    public function requestPartialCancelBilling($no, $cancelReason = '', $price = 0, $remainingAmount = 0);

    /**
     * 결제관련 API 요청
     * @param string $url       API Request url
     * @param array $data       API Request Data
     */
    public function requestApi($url, $data);
}