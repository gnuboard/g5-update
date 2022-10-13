<?php

/**
 * 네이버 스마트스토어 상품 Class
 * @todo 남은 API 연동 진행 > https://apicenter.commerce.naver.com/ko/basic/commerce-api
 * 
 */
class G5SmartstoreCategory {

    /**
     * @var CommerceApi
     */
    public $commerceApi;
    /**
     * @var string 전체 카테고리 조회 URL
     */
    public $urlGetCategories = "https://api.commerce.naver.com/external/v1/categories";
    /**
     * @var string 카테고리 1건 조회 URL
     */
    public $urlGetCategory = "https://api.commerce.naver.com/external/v1/categories";
    /**
     * @var string 하위 카테고리 조회 URL
     */
    public $urlGetChildCategories = "https://api.commerce.naver.com/external/v1/categories/{categoryId}/sub-categories";

    /**
     * @param CommerceApiAuth $commerceApiAuth
     */
    public function __construct($commerceApiAuth)
    {
        $this->commerceApi = new CommerceApi($commerceApiAuth);
    }

    /**
     * 전체 카테고리 조회
     * @return mixed 카테고리 목록
     */
    public function getCategories()
    {
        try {
            // 리프 카테고리만 조회 여부
            $data = array("last" => "false");

            $resultData = $this->commerceApi->requestCurl("GET", $this->urlGetCategories, $data);

            if (isset($resultData->code)) {
                throw new Exception($resultData->message);
            }

            return $resultData;

        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * 카테고리 1건 조회
     * @param string $categoryId 
     * @return mixed 카테고리 정보
     */
    public function getCategory($categoryId)
    {
        try {
            $resultData = $this->commerceApi->requestCurl("GET", $this->urlGetCategory . "/" . $categoryId);

            if (isset($resultData->code)) {
                throw new Exception($resultData->message);
            }

            return $resultData;

        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * 하위 카테고리 조회
     * @param string $categoryId 
     * @return mixed 하위 카테고리 목록
     */
    public function getChiledCategories($categoryId)
    {
        try {

            $url = str_replace("{categoryId}", $categoryId, $this->urlGetChildCategories);

            $resultData = $this->commerceApi->requestCurl("GET", $url);

            if (isset($resultData->code)) {
                throw new Exception($resultData->message);
            }

            return $resultData;

        } catch (Exception $e) {
            return $e;
        }
    }
}
