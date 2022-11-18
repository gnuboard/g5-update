<?php

include_once(dirname(__FILE__) . '/config.php' );

class KcpBatch
{
    /**
     * @var string KCP 사이트 코드
     */
    private $siteCd = '';

    /**
     * @var string 서비스인증서/개인키 파일 경로
     */
    private $pathCert = '';

    /**
     * @var string 서비스인증서 파일이름
     */
    public $filenameServiceCertification = "splCert.pem";

    /**
     * @var string 개인키 파일이름
     */
    public $filenamePrivateKey           = "splPrikeyPKCS8.pem";

    /**
     * @var string 배치 키 발급 API Reqeust URL
     */
    public $urlGetBatchKey = 'https://spl.kcp.co.kr/gw/enc/v1/payment'; // 운영서버

    /**
     * @var string 배치 키를 이용한 결제요청 API Reqeust URL
     */
    public $urlBatchPayment = 'https://spl.kcp.co.kr/gw/hub/v1/payment'; //운영서버

    /**
     * @var string 배치키 삭제 URL
     */
    private $urlDeleteBatchKey = 'https://spl.kcp.co.kr/gw/hub/v1/payment';

    /**
     * @var string 결제취소 요청 API Reqeust URL
     */
    public $urlBatchCancel = 'https://spl.kcp.co.kr/gw/mod/v1/cancel'; // 운영서버

    /**
     * @var string 모바일에 쓰이는 거래등록 URL
     */
    public $urlTradeRegister = 'https://spl.kcp.co.kr/std/tradeReg/register';

    /**
     * @var string
     */
    private $kcpGroupId;

    public function __construct()
    {
        $this->setSiteCd(site_cd);
        $this->setPathCert(path_cert);
        $this->kcpGroupId = kcpgroup_id;

        if(G5_DEBUG){ // 개발 서버설정.
            $this->urlGetBatchKey = 'https://stg-spl.kcp.co.kr/gw/enc/v1/payment';
            $this->urlBatchPayment = 'https://stg-spl.kcp.co.kr/gw/hub/v1/payment';
            $this->urlBatchCancel = 'https://stg-spl.kcp.co.kr/gw/mod/v1/cancel';
            $this->urlDeleteBatchKey = 'https://stg-spl.kcp.co.kr/gw/hub/v1/payment';
            $this->urlTradeRegister = 'https://stg-spl.kcp.co.kr/std/tradeReg/register';
        }
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
     * @return self
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

    /**
     * Get KCP group ID
     *
     * @return string
     */
    public function getKcpGroupId()
    {
        return $this->kcpGroupId;
    }

    /**
     * 서비스 인증서 조회 (직렬화)
     * @param string $path  인증서 경로
     * @return string | false  실패시 false
     */
    public function getServiceCertification($path = '')
    {
        if ($path === '') {
            $path = $this->getPathCert();
        }

        return $this->serializeCertification($path . '/' . $this->filenameServiceCertification);
    }

    /**
     * 인증서 정보 직렬화
     * @param  string $path 인증서 경로
     * @return string | false 실패시 false
     */
    private function serializeCertification($path)
    {
        $certFile = file_get_contents($path);
        if ($certFile === false) {
            return false;
        }
        return preg_replace('/\R/', '', $certFile);
    }

    /**
     * 서명데이터 생성
     * - site_cd(사이트코드) + "^" + tno(거래번호) + "^" + mod_type(취소유형)
     * - NHN KCP로부터 발급받은 개인키(PRIVATE KEY)로 SHA256withRSA 알고리즘을 사용한 문자열 인코딩 값
     * @param string $tno KCP 거래번호
     * @return string | false
     */
    public function createKcpSignData($tno)
    {
        // 결제 취소 (cancel) = site_cd^KCP거래번호^취소유형
        $cancel_target_data = $this->getSiteCd() . '^' . $tno . '^' . 'STSC';

        // 개인키 읽기 ("splPrikeyPKCS8.pem" 은 인증서 개인키)
        $key_data = file_get_contents($this->getPathCert() . '/' . $this->filenamePrivateKey);

        // 개인키 추출, PRIVATE_PW 상수는 개인키 비밀번호. config 에서 로딩.
        $pri_key = openssl_pkey_get_private($key_data, PRIVATE_PW);

        // 결제 취소 signature 생성
        $result = openssl_sign($cancel_target_data, $signature, $pri_key, 'sha256WithRSAEncryption');

        //개인키또는 인증서가 맞지 않음
        if($result === false){
            return false;
        }

        // kcp_sign_data
        return base64_encode($signature);
    }

    /**
     * 자동결제 거래취소
     * @param string $tno KCP 거래번호
     * @return array|string
     */
    public function cancelBatchPayment($tno)
    {
        $data = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'kcp_sign_data' => $this->createKcpSignData($tno),
            'tno'           => $tno,
            'mod_type'      => 'STSC',
            'mod_desc'      => '가맹점 DB 처리 실패(자동취소)'
        );

        return $this->requestApi($this->urlBatchCancel, $data);
    }

    /**
     * 자동결제 거래 부분 취소
     * @param string $tno KCP 거래번호
     * @param int $modMoney 부분취소할 금액
     * @param int $remainMoney 남은 거래금액
     * @param string $modDesc 취소사유
     * @return array|string 취소응답 파라미터
     */
    public function partitalCancelBatchPayment($tno, $modMoney, $remainMoney, $modDesc)
    {
        $data = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'kcp_sign_data' => $this->createKcpSignData($tno),
            'tno'           => $tno,
            'mod_type'      => 'STPC',
            'mod_mny'       => $modMoney,
            'rem_mny'       => $remainMoney,
            'mod_desc'      => $modDesc
        );

        return $this->requestApi($this->urlBatchCancel, $data);
    }

    public function deleteBatchKey($batch_key)
    {
        $data = array(
            "site_cd"        => $this->getSiteCd(),
            "site_key" 	     => '',
            "kcp_cert_info"  => $this->getServiceCertification(),
            "pay_method"     => 'BATCH',
            "batch_key"      => $batch_key, // 결제수단 (고정)
            "group_id"       => $this->getKcpGroupId(),
            "tx_type"        => '10005010' // 거래요청타입(고정)
        );

        return $this->requestApi($this->urlDeleteBatchKey, $data);
    }


    /**
     * @param string $od_id
     * @param string $amount
     * @param string $goodName 상품명
     * @param string $returnUrl 응답결과 받을 url
     * @param string $useEscw  에스크로 사용여부 Y, N
     * @param string $userAgent 필수아님
     * @return array|string
     */
    public function tradeRegister($od_id, $amount, $goodName, $returnUrl, $useEscw, $userAgent = '')
    {
        $data = array(
            'ordr_idxx'     => $od_id,
            "site_cd"       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'good_mny'      => $amount,
            'pay_method'    => 'AUTH', // 결제수단 (고정)
            'good_name'     => $goodName,
            'Ret_URL'       => $returnUrl,
            'escw_used'     => $useEscw, // 에스크로 사용여부 Y, N
            'user_agent'    => $userAgent
        );

        return $this->requestApi($this->urlTradeRegister, $data);
    }

    /**
     * API 요청
     * @param array $data   배치 키 요청데이터
     * @return string | array
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
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        if (!empty($curlError)) {
            return array(
                'msg'=> 'pg 사와의 통신에 문제가 발생했습니다.',
                'http_code' => $curlInfo['http_code'] ); //TODO PHP 5.x 버전 테스트
        }

        return $resData;
    }
}