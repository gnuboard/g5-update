<?php
/**
 * 자동결제 공통 Class
 */
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
    public $filenameServiceCertification = '';

    /**
     * @var string 개인키 파일이름
     */
    public $filenamePrivateKey           = '';

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

    /**
     * @var array  KCP변수 & 공통변수 배열
     * - Key : KCP 요청결과 변수명
     * - Value : 공용 변수명
     */
    public $convert = array(
        'res_cd'    => 'result_code',
        'res_msg'   => 'result_message',
        'ordr_idxx' => 'od_id',
        'order_no'  => 'od_id',
        'batch_key' => 'billing_key',
        'card_cd'   => 'card_code',
        'card_name' => 'card_name',
        'card_mask_no' => 'card_no',
        'tno'       => 'payment_no',
        'amount'    => 'amount',
        'canc_time' => 'cancel_time',
        'mod_mny'   => 'cancel_amount',
        'rem_mny'   => 'refundable_amount',
        'mod_pacn_seq_no' => 'cancel_no'
    );

    public function __construct()
    {
        global $billing_conf;
        
        $this->setSiteCd(site_cd);
        $this->setPathCert(kcp_cert_path);
        $this->setKcpGroupId(kcpgroup_id);

        $this->filenameServiceCertification = $billing_conf['bc_kcp_cert'];
        $this->filenamePrivateKey = $billing_conf['bc_kcp_prikey'];

        if ($billing_conf['bc_kcp_is_test'] == "1") {
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
    public function setKcpGroupId($kcpGroupId)
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
     * @param string $mod_type 취소유형 (STSC : 전체취소, STPC: 부분취소)
     * @return string | false
     */
    public function createKcpSignData($tno, $mod_type = 'STSC')
    {
        // 결제 취소 (cancel) = site_cd^KCP거래번호^취소유형
        $cancel_target_data = $this->getSiteCd() . '^' . $tno . '^' . $mod_type;

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
        if (($curlInfo['http_code'] != 200 && $curlInfo['http_code'] != 201) || !empty($curlError)) {
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
            'currency'      => $data['currency'],
            'quota'         => '00',
            'ordr_idxx'     => $data['od_id'],
            'good_name'     => $data['name'],           /** 권장 @todo 100바이트까지 자르기 */
            'buyr_name'     => $data['mb_name'],        // 선택
            'buyr_mail'     => $data['mb_email'],       // 선택
            'buyr_tel2'     => $data['mb_hp'],          // 선택
            'card_tx_type'  => '11511000',
            'bt_batch_key'  => $data['billing_key'],
            'bt_group_id'   => $this->getKcpGroupId()
        );

        return $this->requestApi($this->urlBatchPayment, $requestData);
    }

    /**
     * 배치키 삭제 요청
     * @param string $batchKey      삭제할 배치키
     * @return mixed
     */
    public function requestDeleteBillKey($batchKey)
    {
        $data = array(
            "site_cd"        => $this->getSiteCd(),
            "site_key" 	     => '',
            "kcp_cert_info"  => $this->getServiceCertification(),
            "pay_method"     => 'BATCH',
            "batch_key"      => $batchKey, // 결제수단 (고정)
            "group_id"       => $this->getKcpGroupId(),
            "tx_type"        => '10005010' // 거래요청타입(고정)
        );

        return $this->requestApi($this->urlDeleteBatchKey, $data);
    }

    /**
     * 배치키 발급을 위해 NHN KCP 모바일 표준결제창 호출에 필요한 거래등록
     * @param string $od_id
     * @param string $amount
     * @param string $goodName 상품명
     * @param string $returnUrl 응답결과 받을 url
     * @param string $useEscw  에스크로 사용여부 Y, N
     * @param string $userAgent 필수아님
     * @return array|string
     */
    public function requestTradeRegister($od_id, $amount, $goodName, $returnUrl, $useEscw, $userAgent = '')
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
     * 자동결제(빌링) 승인취소 요청
     * @param string $tno           NHN KCP 거래 고유번호
     * @param string $cancelReason  취소사유
     * @return mixed
     */
    public function requestCancelBilling($tno, $cancelReason = '가맹점 DB 처리 실패(자동취소)')
    {
        $requestData = array(
            'site_cd'       => $this->getSiteCd(),
            'tno'           => $tno,
            'kcp_cert_info' => $this->getServiceCertification(),
            'kcp_sign_data' => $this->createKcpSignData($tno),
            'mod_type'      => 'STSC',
            'mod_desc'      => $cancelReason
        );

        return $this->requestApi($this->urlBatchCancel, $requestData);
    }

    /**
     * 자동결제(빌링) 승인 부분취소 요청
     * @param string $tno           NHN KCP 거래 고유번호
     * @param string $cancelReason  취소사유
     * @param string $mod_mny       부분취소금액
     * @param string $rem_mny       변경처리 이전 금액 (환불가능 금액)
     * @return mixed
     */
    public function requestPartialCancelBilling($tno, $cancelReason = '가맹점 DB 처리 실패(자동취소)', $mod_mny = 0, $rem_mny = 0)
    {
        $requestData = array(
            'site_cd'       => $this->getSiteCd(),
            'kcp_cert_info' => $this->getServiceCertification(),
            'kcp_sign_data' => $this->createKcpSignData($tno, 'STPC'),
            'mod_type'      => 'STPC',
            'tno'           => $tno,
            'mod_mny'       => $mod_mny,
            'rem_mny'       => $rem_mny,
            'mod_desc'      => $cancelReason
        );

        return $this->requestApi($this->urlBatchCancel, $requestData);
    }
}
