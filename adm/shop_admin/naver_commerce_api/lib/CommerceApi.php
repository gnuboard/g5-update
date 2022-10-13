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
                $url .= "?" . htmlspecialchars(http_build_query($data), ENT_QUOTES, 'UTF-8');
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
}