<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 나이스페이 공통 설정
require_once(G5_MSHOP_PATH.'/settle_nicepay.inc.php');

add_log($_POST);

/*
****************************************************************************************
* <Authentication Result Parameter>
****************************************************************************************
*/
$authResultCode = isset($_POST['AuthResultCode']) ? clean_xss_tags($_POST['AuthResultCode']) : '';		// authentication result code 0000:success
$authResultMsg = isset($_POST['AuthResultMsg']) ? clean_xss_tags($_POST['AuthResultMsg']) : '';		// authentication result message
$nextAppURL = isset($_POST['NextAppURL']) ? clean_xss_tags($_POST['NextAppURL']) : '';				// authorization request URL
$txTid = isset($_POST['TxTid']) ? clean_xss_tags($_POST['TxTid']) : '';						// transaction ID
$authToken = isset($_POST['AuthToken']) ? clean_xss_tags($_POST['AuthToken']) : '';				// authentication TOKEN
$payMethod = isset($_POST['PayMethod']) ? clean_xss_tags($_POST['PayMethod']) : '';				// payment method
$mid = isset($_POST['MID']) ? clean_xss_tags($_POST['MID']) : '';							// merchant id
$moid = isset($_POST['Moid']) ? clean_xss_tags($_POST['Moid']) : '';							// order number
$amt = isset($_POST['Amt']) ? (int) preg_replace('/[^0-9]/', '', $_POST['Amt']) : 0;							// Amount of payment
$reqReserved = isset($_POST['ReqReserved']) ? clean_xss_tags($_POST['ReqReserved']) : '';			// mall custom field 
$netCancelURL = isset($_POST['NetCancelURL']) ? clean_xss_tags($_POST['NetCancelURL']) : '';			// netCancelURL

if (get_session('ss_order_id') != $moid){
    alert("요청한 주문번호가 틀려서 결제를 진행할수 없습니다.\n다시 장바구니에서 시도해 주세요.");
}

if ($default['de_nicepay_mid'] != $mid) {
    alert("요청한 상점 mid와 설정된 mid가 틀리므로 결제를 진행할수 없습니다.");
}

// API CALL foreach example
function jsonRespDump($resp){
	$respArr = json_decode($resp);
	foreach ( $respArr as $key => $value ){
		echo "$key=". $value."<br />";
	}
}

if (! function_exists('nicepay_reqPost')) {
    //Post api call
    function nicepay_reqPost($data, $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);					//connection timeout 15 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));	//POST data
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        curl_close($ch);	 
        return $response;
    }
}

if (! function_exists('nicepay_res')) {
    function nicepay_res($key, $data, $default_val='') {
        $response_val = isset($data[$key]) ? $data[$key] : $default_val;

        return ($response_val ? $response_val : $default_val);
    }
}

/*
****************************************************************************************
* <authorization parameters init>
****************************************************************************************
*/
$response = "";

if($authResultCode === "0000"){
	/*
	****************************************************************************************
	* <Hash encryption> (do not modify)
	****************************************************************************************
	*/
	$ediDate = preg_replace('/[^0-9]/', '', G5_TIME_YMDHIS);
	$merchantKey = $default['de_nicepay_key']; // 상점키
	$signData = bin2hex(hash('sha256', $authToken . $mid . $amt . $ediDate . $merchantKey, true));

	try{
		$data = Array(
			'TID' => $txTid,
			'AuthToken' => $authToken,
			'MID' => $mid,
			'Amt' => $amt,
			'EdiDate' => $ediDate,
			'SignData' => $signData,
			'CharSet' => 'utf-8'
		);		
		/*
		****************************************************************************************
		* <authorization request>
		* authorization through server to server communication.
		****************************************************************************************

        // 3001 : 신용카드 성공코드
        // 4000 : 계좌이체 성공코드
        // 4100 : 가상계좌 발급 성공코드
        // A000 : 휴대폰 소액결제 성공코드
        // 7001 : 현금영수증
        // https://developers.nicepay.co.kr/manual-auth.php
		*/			
		$response = nicepay_reqPost($data, $nextAppURL);
        $respArr = json_decode($response, true);
        
        $ResultCode = nicepay_res('ResultCode', $respArr);
        $tno             = nicepay_res('TID', $respArr);
        $amount          = (int) nicepay_res('Amt', $respArr, 0);
        $app_time        = nicepay_res('AuthDate', $respArr);
        $pay_method = nicepay_res('PayMethod', $respArr);
        $app_no    = nicepay_res('AuthCode', $respArr); // 승인 번호  (신용카드, 계좌이체, 휴대폰)
        $pay_type   = $NICEPAY_METHOD[$pay_method];

        if ($ResultCode == '3001') {    // 신용카드

            $card_cd   = nicepay_res('CardCode', $respArr); // 카드사 코드
            $card_name = nicepay_res('CardName', $respArr); // 카드 종류

        } else if ($ResultCode == '4100') {    // 가상계좌

            $bank_name = $bankname = nicepay_res('VbankBankName', $respArr);
            $account = nicepay_res('VbankNum', $respArr);
            $va_date   = nicepay_res('VbankExpDate', $respArr).' '.nicepay_res('VbankExpTime', $respArr); // 가상계좌 입금마감시간
            $app_no    = nicepay_res('VbankNum', $respArr);

            if ($default['de_escrow_use'] == 1)
                $escw_yn         = 'Y';

        } else if ($ResultCode == '4000') {     // 계좌이체
            $bank_name = $bankname = nicepay_res('BankName', $respArr);
            $bank_code = nicepay_res('BankCode', $respArr);

            $RcptType = nicepay_res('RcptType', $respArr); // 현금영수증타입 (0:발행안함,1:소득공제,2:지출증빙)
            $RcptTID = nicepay_res('RcptTID', $respArr); // 현금영수증 TID, 현금영수증 거래인 경우 리턴
            $RcptAuthCode = nicepay_res('RcptAuthCode', $respArr); // 현금영수증 승인번호, 현금영수증 거래인 경우 리턴

            if ($default['de_escrow_use'] == 1)
                $escw_yn         = 'Y';

        }
        $depositor       = '';  // 입금할 계좌 예금주
        $account         = nicepay_res('VbankNum', $respArr);
        $commid          = '';    // 통신사 코드
        $mobile_no       = '';    // 휴대폰결제시 휴대폰번호
        $app_no = $od_app_no = nicepay_res('VbankNum', $respArr);
        $card_name       = nicepay_res('CardName', $respArr);

	} catch(Exception $e) {
		$e->getMessage();
		$data = Array(
			'TID' => $txTid,
			'AuthToken' => $authToken,
			'MID' => $mid,
			'Amt' => $amt,
			'EdiDate' => $ediDate,
			'SignData' => $signData,
			'NetCancel' => '1',
			'CharSet' => 'utf-8'
		);
		/*
		*************************************************************************************
		* <NET CANCEL>
		* If an exception occurs during communication, cancelation is recommended
		*************************************************************************************
		*/			
		$response = nicepay_reqPost($data, $netCancelURL);
		// jsonRespDump($response);

        alert("결제 오류로 더 이상 진행할수 없습니다.");
	}	
	
}else{
	//When authentication fail
	$ResultCode = $authResultCode; 	
	$ResultMsg = $authResultMsg;

    alert($ResultMsg.' 실패 코드 : '.$ResultCode);
}