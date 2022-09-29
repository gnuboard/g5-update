<?php

/**
 * 커머스API Request Class
 * @todo commerceApiAuth > authorizationHeader 값을 가져오기 위해서 더 나은 방법이 있는지 확인
 */
class CommerceApi {

    public $commerceApiAuth = null;

    /**
     * @param CommerceApiAuth $commerceApiAuth
     */
    public function __construct($commerceApiAuth = null)
    {
        if (isset($commerceApiAuth)) {
            $this->commerceApiAuth = $commerceApiAuth;
        }
    }

    /**
     * CURL Request
     * 
     * @param string $method    HTTP request method
     * @param string $url       커머스API 요청 URL
     * @param array $data       전송 Data
     */
    public function requestCurl($method, $url, $data = array())
    {   
        $curlHandle = curl_init();

        // Access Token Header 추가
        if (isset($this->commerceApiAuth)) {
            $authorizationHeader =$this->commerceApiAuth->getAuthorizationHeader();
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array($authorizationHeader));
        }

        // CURL 옵션설정
        if ($method === "GET") {
            $url .= "?" . htmlspecialchars(http_build_query($data), ENT_QUOTES, 'UTF-8');
        } elseif ($method === "POST") {
            curl_setopt($curlHandle, CURLOPT_POST, true);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        // CURL 결과처리
        $response = curl_exec($curlHandle);
        $curl_err = curl_error($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);

        $isSuccess = $httpCode == 200;
        $responseJson = json_decode($response);

        return $responseJson;
    }
}