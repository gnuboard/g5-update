<?php

include_once(dirname(__FILE__) . '/config.php' );

class G5BillingKcp implements BillingInterface
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
     * @var string 결제취소 요청 API Reqeust URL
     */
    public $urlBatchCancel = 'https://spl.kcp.co.kr/gw/mod/v1/cancel'; // 운영서버

    /**
     * @var string
     */
    private $kcpGroupId;

    /**
     * @var array  KCP변수 & 공통변수 배열
     * - Key : KCP 요청결과 변수명
     * - Value : 공용 변수명
     */
    public $convert = array(
        'res_cd'    => 'result_code',
        'res_msg'   => 'result_msg',
        'ordr_idxx' => 'od_id',
        'order_no'  => 'od_id',
        'batch_key' => 'bill_key',
        'card_cd'   => 'card_code',
        'card_name' => 'card_name',
        'tno'       => 'billing_no',
        'amount'    => 'amount'
    );

    public function __construct()
    {
        $this->setSiteCd(site_cd);
        $this->setPathCert(path_cert);
        $this->setKcpGroupId(kcpgroup_id);

        if (G5_DEBUG) { // 개발 서버설정.
            $this->urlGetBatchKey = 'https://stg-spl.kcp.co.kr/gw/enc/v1/payment';
            $this->urlBatchPayment = 'https://stg-spl.kcp.co.kr/gw/hub/v1/payment';
            $this->urlBatchCancel = 'https://stg-spl.kcp.co.kr/gw/mod/v1/cancel';
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
     * Get the value of kcpGroupId
     *
     * @return  string
     */ 
    public function getKcpGroupId()
    {
        return $this->kcpGroupId;
    }

    /**
     * Set the value of kcpGroupId
     *
     * @param  string  $kcpGroupId
     *
     * @return  self
     */ 
    public function setKcpGroupId(string $kcpGroupId)
    {
        $this->kcpGroupId = $kcpGroupId;

        return $this;
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
     * API 요청
     * @param string $url       API Request url
     * @param array $data       API Request Data
     * @return mixed
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
                'result_msg'=> 'pg 사와의 통신에 문제가 발생했습니다.',
                'http_code' => $curlInfo['http_code'] ); //TODO PHP 5.x 버전 테스트
        }

        // RES JSON DATA Parsing
        return json_decode($resData, true);
    }

    /**
     * 빌링 키 발급 요청
     * @param array $data   Request Data
     * @return mixed
     */
    public function requestIssueBillKey($data = array())
    {
        // 인증서 정보 추가 (직렬화)
        $requestData = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            "tran_cd"       => $data["tran_cd"],  // 요청코드
            "enc_data"      => $data["enc_data"], // 암호화 인증데이터
            "enc_info"      => $data["enc_info"]  // 암호화 인증데이터
        );

        return $this->requestApi($this->urlGetBatchKey, $requestData);
    }

    /**
     * 자동결제(빌링) 승인 요청
     * @param array $data   Request Data
     * @return mixed
     */
    public function requestBilling($data = array())
    {
        $requestData = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'pay_method'    => 'CARD',
            'cust_ip'       => '',
            'amount'        => $data['amount'],
            'card_mny'      => $data['amount'],
            'currency'      => '410',
            'quota'         => '00',
            'ordr_idxx'     => $data['od_id'],
            'good_name'     => $data['service_name'], /** 권장 @todo 100바이트까지 자르기 */
            'buyr_name'     => $data['mb_name'],        // 선택
            'buyr_mail'     => $data['mb_email'],       // 선택
            'buyr_tel2'     => $data['mb_tel'],         // 선택
            'card_tx_type'  => '11511000',
            'bt_batch_key'  => $data['batch_key'],
            'bt_group_id'   => $this->getKcpGroupId()
        );

        return $this->requestApi($this->urlBatchPayment, $requestData);
    }

    /**
     * 자동결제(빌링) 승인취소 요청
     * @param string $no            PG사 거래번호
     * @param string $cancelReason  취소사유
     * @return mixed
     */
    public function requestCancelBilling($no, $cancelReason = '가맹점 DB 처리 실패(자동취소)')
    {
        $requestData = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'kcp_sign_data' => $this->createKcpSignData($no),
            'tno'           => $no,
            'mod_type'      => 'STSC',
            'mod_desc'      => $cancelReason
        );

        return $this->requestApi($this->urlBatchCancel, $requestData);
    }
}