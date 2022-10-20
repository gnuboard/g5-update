<?php

/**
 * 커머스API Request Class
 * 
 * @todo commerceApiAuth > authorizationHeader 값을 가져오기 위해서 더 나은 방법이 있는지 확인
 * 
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
     * - GET방식의 날짜 데이터는 convertTimeFormat으로 변환한 값을 URL에 직접 추가해서 전달받는다
     * 
     * @param string $method    HTTP request method
     * @param string $url       커머스API 요청 URL
     * @param array $data       전송 Data
     * @return mixed
     */
    public function requestCurl($method, $url, $data = array())
    {   
        $curlHandle = curl_init();

        /* HTTP Header */
        $header = array();        
        if (isset($this->commerceApiAuth)) {
            array_push($header, $this->commerceApiAuth->getAuthorizationHeader());
        }
        if ($url == G5SmartstoreProduct::$urlCreateChannelProduct
            || (strpos($url, G5SmartstoreProduct::$urlUpdateChannelProduct) !== false && $method == "PUT")) {
            array_push($header, "content-type: application/json");
        } elseif ($url == G5SmartstoreProduct::$urlUploadProductImage) {
            array_push($header, "content-type: multipart/form-data");
        }

        /* CURL option Setting */
        if ($method === "GET") {
            if (isset($data)) {
                if (strpos($url, '?') !== false) {
                    $url .= "&";
                } else {
                    $url .= "?";
                }
                $url .= htmlspecialchars(http_build_query($data), ENT_QUOTES, 'UTF-8');
            }
        } elseif ($method === "POST" || $method == "PUT") {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        /* CURL result */
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);

        return json_decode($response);
    }

    /**
     * 날짜형식을 커머스 API 전달형식에 맞게 변환 (ISO 8601)
     * @param string $date 날짜
     * @return string
     */
    public function convertTimeFormat($date)
    {
        return date('Y-m-d\TH:i:s', strtotime($date)) . substr(microtime(), 1, 2) . urlencode(date('O'));
    }
}