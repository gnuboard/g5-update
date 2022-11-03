<?php
class KcpBatch
{
    public $siteCd = null;
    
    public $pathCert = "";
    
    public $filenameServiceCertification    = "splCert.pem";
    public $filenamePrivateKey              = "splPrikeyPKCS8.pem";

    public $urlGetBatchKey = "https://stg-spl.kcp.co.kr/gw/enc/v1/payment"; // 개발서버
    //public $getBatchKeyURL = "https://spl.kcp.co.kr/gw/enc/v1/payment"; // 운영서버

    public $urlBatchPayment = "https://stg-spl.kcp.co.kr/gw/hub/v1/payment"; // 개발서버
    //public $urlBatchPayment = "https://spl.kcp.co.kr/gw/hub/v1/payment"; // 운영서버

    public $urlBatchCancel = "https://stg-spl.kcp.co.kr/gw/mod/v1/cancel"; // 개발서버
    // public $urlBatchCancel = "https://spl.kcp.co.kr/gw/mod/v1/cancel"; // 운영서버
    
    public function __construct()
    {
        global $site_cd, $path_cert;

        $this->setSiteCd($site_cd);
        $this->setPathCert($path_cert);
    }

    /**
     * 직렬화 서비스 인증서 조회
     * @param string $path  인증서 경로
     * @return string
     */
    public function getServiceCertification($path = "")
    {
        if ($path == "") {
            $path = $this->getPathCert();
        }

        return $this->serializeCertification($path . $this->filenameServiceCertification);
    }

    /**
     * 직렬화 개인키 조회
     * @param string $path  개인키 경로
     * @return string
     */
    public function getPrivateKey($path = "")
    {
        if ($path == "") {
            $path = $this->getPathCert();
        }

        return $this->serializeCertification($path . $this->filenamePrivateKey);
    }

    /**
     * 인증서 정보 직렬화
     * @param $path string 인증서 경로
     * @return string
     */
    public function serializeCertification($path)
    {
        return (string)str_replace("\n", "", (string)file_get_contents($path));
    }

    /**
     * 서명데이터 생성
     * - site_cd(사이트코드) + "^" + tno(거래번호) + "^" + mod_type(취소유형)
     * - NHN KCP로부터 발급받은 개인키(PRIVATE KEY)로 SHA256withRSA 알고리즘을 사용한 문자열 인코딩 값
     * @param string $tno KCP 거래번호
     * @return string
     */
    public function createKcpSignData($tno)
    {
        // 결제 취소 (cancel) = site_cd^KCP거래번호^취소유형
        $cancel_target_data = $this->getSiteCd() . "^" . $tno . "^" . "STSC";
        
        // 개인키 경로 ("splPrikeyPKCS8.pem" 은 테스트용 개인키) / privatekey 파일 read
        $key_data = file_get_contents($this->getPathCert . $this->filenamePrivateKey);
        
        // privatekey 추출, 'changeit' 은 테스트용 개인키비밀번호
        $pri_key = openssl_pkey_get_private($key_data, 'changeit');

        // 결제 취소 signature 생성
        openssl_sign($cancel_target_data, $signature, $pri_key, 'sha256WithRSAEncryption');

        // kcp_sign_data
        return base64_encode($signature);
    }

    /**
     * 자동결제 거래취소
     * @param string $tno KCP 거래번호
     * @return string|bool
     */
    public function cancelBatchPayment($tno)
    {
        $data = array(
            "site_cd"        => $this->getSiteCd(),
            "kcp_cert_info"  => $this->getServiceCertification(),
            "kcp_sign_data"  => $this->createKcpSignData($tno),
            "tno"            => $tno,
            "mod_type"       => "STSC",
            "mod_desc"       => "가맹점 DB 처리 실패(자동취소)"
        );
        
        return $this->requestApi($this->urlBatchCancel, $data);
    }

    /**
     * API 요청
     * @param array $data   배치 키 요청데이터
     * @return string|bool
     */
    public function requestApi($url, $data)
    {
        $reqData       = json_encode($data);
        $headerData    = array("Content-Type: application/json", "charset=utf-8");
        
        // API REQ
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        // API RES
        $resData  = curl_exec($ch);

        curl_close($ch);

        return $resData;
    }

    /**
     * Get the value of pathCert
     */ 
    public function getPathCert()
    {
        return $this->pathCert;
    }

    /**
     * Set the value of pathCert
     *
     * @return  self
     */ 
    public function setPathCert($pathCert)
    {
        $this->pathCert = $pathCert;

        return $this;
    }

    /**
     * Get the value of siteCd
     */ 
    public function getSiteCd()
    {
        return $this->siteCd;
    }

    /**
     * Set the value of siteCd
     *
     * @return  self
     */ 
    public function setSiteCd($siteCd)
    {
        $this->siteCd = $siteCd;

        return $this;
    }
}