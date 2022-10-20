<?php

/**
 * 커머스API 상품문의 Class
 */
class G5SmartstoreQnas {

    public $commerceApiAuth = null;

    /**
     * @var string 상품 문의 목록 조회 URL
     */
    public $urlGetQnas = "https://api.commerce.naver.com/external/v1/contents/qnas";
    /**
     * @var string 상품 문의 답변 템플릿 목록 조회
     */
    public $urlGetQnasTemplate = "https://api.commerce.naver.com/external/v1/contents/qnas/templates";
    /**
     * @var string 상품 문의 답변 등록/수정
     */
    public $urlUpdateQnas = "https://api.commerce.naver.com/external/v1/contents/qnas/{questionId}";

    /**
     * @param CommerceApiAuth $commerceApiAuth
     */
    public function __construct($commerceApiAuth = null)
    {
        $this->commerceApi = new CommerceApi($commerceApiAuth);
    }

    /**
     * 상품 문의 목록 조회
     * - 날짜는 url에 직접 추가해서 전달한다.
     * 
     * @param string    $fromDate   검색시작일
     * @param string    $toDate     검색종료일(ISO 8601) 예시 : 2022-09-29T17:32:06.0%2B09:00
     * @param int       $page       페이지번호
     * @param int       $size       페이지크기
     * @param boolean   $answered   답변여부
     * @return mixed
     */
    public function getQnas($fromDate, $toDate, $page = 1, $size = 100, $answered = null)
    {
        // 리프 카테고리만 조회 여부
        $data = array();
        if (isset($page)) {
            $data['page'] = $page;
        }
        if (isset($size)) {
            $data['size'] = $size;
        }
        if (isset($answered)) {
            $data['answered'] = $answered;
        }

        // 커머스API 날짜형식으로 변환 (예시 : 2022-09-29 → 2022-09-29T17:32:06.0%2B09:00)
        $fromDate   = $this->commerceApi->convertTimeFormat($fromDate);
        $toDate     = $this->commerceApi->convertTimeFormat($toDate);

        $resultData = $this->commerceApi->requestCurl("GET", $this->urlGetQnas . "?fromDate=" . $fromDate . "&toDate=" . $toDate, $data);

        return $resultData;
    }

    /**
     * 상품 문의 답변 등록/수정
     * @param int       $questionId     상품 문의 ID
     * @param string    $commentContent 상품 문의 답변 내용
     * @return mixed
     */
    public function updateQnas($questionId, $commentContent)
    {
        $url = str_replace("{questionId}", $questionId, $this->urlUpdateQnas);

        $data = array(
            "commentContent" => $commentContent
        );

        $resultData = $this->commerceApi->requestCurl("PUT", $url, $data);

        return $resultData;
    }
}