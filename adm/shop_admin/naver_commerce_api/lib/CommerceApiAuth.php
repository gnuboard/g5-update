<?php

/**
 * 네이버 커머스API 인증
 * - 커머스API 애플리케이션 관련 값들은 커머스API 계정등록 이후 아래 링크에서 확인할 수 있다.
 * 
 * @link https://apicenter.commerce.naver.com/ko/member/home
 * 
 */
class CommerceApiAuth {

    /**
     * @var string 커머스API 애플리케이션 ID
     */
    private $clientId = '';
    /**
     * @var string 커머스API 애플리케이션 시크릿
     * bcrypt 암호화에서 고정 salt로 사용.
     */
    private $clientSecret = '';
    /**
     * @var string Unix Timestamp 13digit
     */
    private $timestamp;
    /**
     * @var string 전자서명
     */
    private $signature;
    /**
     * @var string
     */
    private $urlAccessToken = "https://api.commerce.naver.com/external/v1/oauth2/token";
    /**
     * @var string authorizationHeader에 인증 토큰을 추가한 값
     */
    public $authorizationHeader = '';

    /**
     * @param string $clientId              커머스API 애플리케이션 ID
     * @param string $clientSecret          커머스API 애플리케이션 시크릿
     * @param SignatureInterface $signatureInterface 전자서명 생성 Class
     */
    public function __construct($clientId, $clientSecret, SignatureInterface $signatureInterface)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->timestamp    = (int)round(microtime(true) * 1000);
        
        // 전자서명 생성
        $this->signature = $signatureInterface->generateSignature($this->clientId, $this->clientSecret, $this->timestamp);

        // 헤더 값 설정
        $this->setAuthorizationHeader($this->getAccessToken());

        // 헤더 값 출력 (테스트)
        echo $this->getAuthorizationHeader();
    }

    /**
     * Access Token 발급
     * @return string
     */
    public function getAccessToken()
    {
        try {
            $commerceApi = new CommerceApi();

            $apiData = array(
                'client_id' => $this->clientId,
                'timestamp' => $this->timestamp,
                'client_secret_sign' => $this->signature,
                'grant_type' => 'client_credentials',
                'type' => 'SELF'
            );
            print_r($this->signature);
            echo "<br>";
    
            $resultData = $commerceApi->requestCurl("POST", $this->urlAccessToken, $apiData);

            if (empty($resultData->access_token)){
                throw new Exception("엑세스 토큰이 없습니다.");
            }
    
            return $resultData->access_token;
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    /**
     * set authorizationHeader
     * 
     * @param string $accessToken
     * @return void
     */
    public function setAuthorizationHeader($accessToken)
    {
        $this->authorizationHeader = "Authorization: Bearer " . $accessToken;
    }
    /**
     * get authorizationHeader
     * 
     * @return string
     */
    public function getAuthorizationHeader()
    {
        return $this->authorizationHeader;
    }

}
