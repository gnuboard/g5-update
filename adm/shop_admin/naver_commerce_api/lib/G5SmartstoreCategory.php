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

            return $this->convertResponseToCategoriesArray($resultData);

        } catch (Exception $e) {
            print_r($e->getMessage());
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

    /**
     * 전체 카테고리 목록 > PHP 계층구조 배열로 변환
     * @param stdClass $response API 응답결과
     * @return array
     */
    public function convertResponseToCategoriesArray($response)
    {
        /* 카테고리 배열 생성 */
        $category = array();
        $categoryInfo1 = array("id" => "", "name" => "");
        $categoryInfo2 = array("id" => "", "name" => "");
        $categoryInfo3 = array("id" => "", "name" => "");

        foreach ($response as $key => $value) {
            $wholeCategoryName  = $response[$key]->wholeCategoryName;
            $categoryName       = $response[$key]->name;
            $categoryId         = $response[$key]->id;

            if ($wholeCategoryName == $categoryName) {
                $categoryInfo1['id']        = $categoryId;
                $categoryInfo1['name']      = $categoryName;
                $category[$categoryId] = $response[$key];

            } elseif ($wholeCategoryName == $categoryInfo1['name'] . ">" . $categoryName) {
                $categoryInfo2['id']        = $categoryId;
                $categoryInfo2['name']      = $categoryName;
                $category[$categoryInfo1['id']]->child[$categoryId] = $response[$key];

            } elseif ($wholeCategoryName == $categoryInfo1['name'] . ">" . $categoryInfo2['name'] . ">" . $categoryName) {
                $categoryInfo3['id']        = $categoryId;
                $categoryInfo3['name']      = $categoryName;
                $category[$categoryInfo1['id']]->child[$categoryInfo2['id']]->child[$categoryId] = $response[$key];

            } elseif ($wholeCategoryName == $categoryInfo1['name'] . ">" . $categoryInfo2['name'] . ">" . $categoryInfo3['name'] . ">" . $categoryName) {
                $category[$categoryInfo1['id']]->child[$categoryInfo2['id']]->child[$categoryInfo3['id']]->child[$categoryId] = $response[$key];
            }
        }

        return $category;
    }
}
