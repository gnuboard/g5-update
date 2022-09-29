<?php

/**
 * 네이버 스마트스토어 상품 Class
 * @todo 남은 API 연동 진행 > https://apicenter.commerce.naver.com/ko/basic/commerce-api
 * 
 */
class G5SmartstoreProduct {

    public $commerceApi;
    public $commerceApiAuth;

    /**
     * @var string 채널 상품 조회 URL
     */
    public $urlGetChannelProduct = "https://api.commerce.naver.com/external/v1/products/channel-products/";

    /**
     * @param CommerceApiAuth $commerceApiAuth
     */
    public function __construct($commerceApiAuth)
    {
        $this->commerceApi = new CommerceApi($commerceApiAuth);
    }

    /**
     * 채널 상품 조회
     * @param integer<int64> $channelProductNo  채널상품번호 (스마트스토어 상품번호)
     */
    public function getChannelProduct($channelProductNo)
    {
        $resultData = $this->commerceApi->requestCurl("GET", $this->urlGetChannelProduct . $channelProductNo);

        print_r($resultData);
    }
}