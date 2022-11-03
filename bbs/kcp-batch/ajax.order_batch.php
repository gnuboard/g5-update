<?php
header("Content-type: text/html; charset=utf-8");

include_once('./_common.php');

/* ============================================================================== */
/* =   API URL                                                                  = */
/* = -------------------------------------------------------------------------- = */
$target_URL = "https://stg-spl.kcp.co.kr/gw/hub/v1/payment"; // 개발서버
//$target_URL = "https://spl.kcp.co.kr/gw/hub/v1/payment"; // 운영서버
/* ============================================================================== */
/* =  요청정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
$site_cd            = $_POST["site_cd"]; // 사이트코드
// 인증서 정보(직렬화)
$filePath       = G5_PATH . "/bbs/kcp-batch/certificate/splCert.pem";
$kcp_cert_info  = str_replace("\n", "", file_get_contents($filePath));
// $kcp_cert_info      = "-----BEGIN CERTIFICATE-----MIIDgTCCAmmgAwIBAgIHBy4lYNG7ojANBgkqhkiG9w0BAQsFADBzMQswCQYDVQQGEwJLUjEOMAwGA1UECAwFU2VvdWwxEDAOBgNVBAcMB0d1cm8tZ3UxFTATBgNVBAoMDE5ITktDUCBDb3JwLjETMBEGA1UECwwKSVQgQ2VudGVyLjEWMBQGA1UEAwwNc3BsLmtjcC5jby5rcjAeFw0yMTA2MjkwMDM0MzdaFw0yNjA2MjgwMDM0MzdaMHAxCzAJBgNVBAYTAktSMQ4wDAYDVQQIDAVTZW91bDEQMA4GA1UEBwwHR3Vyby1ndTERMA8GA1UECgwITG9jYWxXZWIxETAPBgNVBAsMCERFVlBHV0VCMRkwFwYDVQQDDBAyMDIxMDYyOTEwMDAwMDI0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAppkVQkU4SwNTYbIUaNDVhu2w1uvG4qip0U7h9n90cLfKymIRKDiebLhLIVFctuhTmgY7tkE7yQTNkD+jXHYufQ/qj06ukwf1BtqUVru9mqa7ysU298B6l9v0Fv8h3ztTYvfHEBmpB6AoZDBChMEua7Or/L3C2vYtU/6lWLjBT1xwXVLvNN/7XpQokuWq0rnjSRThcXrDpWMbqYYUt/CL7YHosfBazAXLoN5JvTd1O9C3FPxLxwcIAI9H8SbWIQKhap7JeA/IUP1Vk4K/o3Yiytl6Aqh3U1egHfEdWNqwpaiHPuM/jsDkVzuS9FV4RCdcBEsRPnAWHz10w8CX7e7zdwIDAQABox0wGzAOBgNVHQ8BAf8EBAMCB4AwCQYDVR0TBAIwADANBgkqhkiG9w0BAQsFAAOCAQEAg9lYy+dM/8Dnz4COc+XIjEwr4FeC9ExnWaaxH6GlWjJbB94O2L26arrjT2hGl9jUzwd+BdvTGdNCpEjOz3KEq8yJhcu5mFxMskLnHNo1lg5qtydIID6eSgew3vm6d7b3O6pYd+NHdHQsuMw5S5z1m+0TbBQkb6A9RKE1md5/Yw+NymDy+c4NaKsbxepw+HtSOnma/R7TErQ/8qVioIthEpwbqyjgIoGzgOdEFsF9mfkt/5k6rR0WX8xzcro5XSB3T+oecMS54j0+nHyoS96/llRLqFDBUfWn5Cay7pJNWXCnw4jIiBsTBa3q95RVRyMEcDgPwugMXPXGBwNoMOOpuQ==-----END CERTIFICATE-----";
$cust_ip            = "";
$currency           = $_POST[ "currency" ];
$quota              = "";

$ordr_idxx          = $_POST[ "ordr_idxx" ];
$good_name          = $_POST[ "good_name" ];
$buyr_name          = $_POST[ "buyr_name" ];
$buyr_mail          = $_POST[ "buyr_mail" ];
$buyr_tel2          = $_POST[ "buyr_tel2" ];

$bt_batch_key       = $_POST[ "bt_batch_key" ]; // 배치키 정보
$bt_group_id        = $_POST[ "bt_group_id" ];  // 배치키 그룹아이디

$recurring_count    = '1';//$_POST["recurring_count"];
$interval_unit      = 'm';//$_POST["interval_unit"];
// 결제결과 후처리 성공여부 (false일때 결제 취소처리)
$bSucc = "";

$data = array(
    "site_cd"        => $site_cd,
    "kcp_cert_info"  => $kcp_cert_info,
    "pay_method"     => "CARD",
    "cust_ip"        => "",
    "amount"         => $_POST[ "good_mny" ],
    "card_mny"       => $_POST[ "good_mny" ],
    "currency"       => $currency,
    "quota"          => "00",
    "ordr_idxx"      => $ordr_idxx,
    "good_name"      => $good_name,
    "buyr_name"      => $buyr_name,
    "buyr_mail"      => $buyr_mail,
    "buyr_tel2"      => $buyr_tel2,
    "card_tx_type"   => "11511000",
    "bt_batch_key"   => $bt_batch_key,
    "bt_group_id"    => $bt_group_id
);

$req_data = json_encode($data);

$header_data = array( "Content-Type: application/json", "charset=utf-8" );

// API REQ
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $req_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// API RES
$res_data  = curl_exec($ch);

/* ============================================================================== */
/* =  로그 생성                                                                  = */
/* = -------------------------------------------------------------------------- = */


/* ============================================================================== */
/* =  응답정보                                                                   = */
/* = -------------------------------------------------------------------------- = */
// 공통
$res_cd = "";
$res_msg = "";
$tno = "";
$amount = "";
$order_no = "";
// 카드
$card_cd = "";
$card_name = "";
$app_no = "";
$app_time ="";
$quota ="";
$noinf ="";

// RES JSON DATA Parsing
$json_res = json_decode($res_data, true);

$res_cd = $json_res["res_cd"];
$res_msg = $json_res["res_msg"];

if ( $res_cd == "0000" )
{
    $tno = $json_res["tno"];
    $amount = $json_res["amount"];
    $card_cd = $json_res["card_cd"];
    $card_name = $json_res["card_name"];
    $app_no = $json_res["app_no"];
    $order_no = $json_res["order_no"];
    $app_time = $json_res["app_time"];
    $quota = $json_res["quota"];
    $noinf = $json_res["noinf"];
}

curl_close($ch);



/* ============================================================================== */
/* =  결제 결과처리                                                              = */
/* ============================================================================== */
// 자동결제 정보 저장
$start_date = date("Y-m-d H:i:s");
$end_date = "0000-00-00 00:00:00";

$g5["batch_info_table"] = "g5_batch_info";
$sql_batch_info = "INSERT INTO {$g5["batch_info_table"]} SET 
                od_id               = '{$ordr_idxx}',
                mb_id               = '{$member['mb_id']}',
                batch_key           = '{$bt_batch_key}',
                kcpgroup_id         = '{$bt_group_id}',
                price               = '{$amount}',
                recurring_count     = '{$recurring_count}',
                interval_unit       = '{$interval_unit}',
                start_date          = '{$start_date}',
                end_date            = '{$end_date}'
            ";
if (!sql_query($sql_batch_info)) {
    $bSucc = "false";
}

// 자동결제 이력 저장
$g5["batch_payment_table"] = "g5_batch_payment";
$sql_payment = "INSERT INTO {$g5["batch_payment_table"]} SET 
                od_id               = '{$ordr_idxx}',
                mb_id               = '{$member["mb_id"]}',
                batch_key           = '{$bt_batch_key}',
                amount              = '{$amount}',
                res_cd              = '{$res_cd}',
                res_msg             = '{$res_msg}',
                tno                 = '{$tno}',
                card_name           = '{$card_name}',
                res_data            = '{$res_data}',
                date                = '{$start_date}'
            ";
if (!sql_query($sql_payment)) {
    $bSucc = "false";
}
/* 
==========================================================================
승인 결과 DB 처리 실패시 : 자동취소
--------------------------------------------------------------------------
승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해
DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로
승인 취소 요청을 하는 프로세스가 구성되어 있습니다.

DB 작업이 실패 한 경우, bSucc 라는 변수의 값을 "false"로 설정해 주시기 바랍니다.
(DB 작업 성공의 경우에는 "false" 이외의 값을 설정하시면 됩니다.)
--------------------------------------------------------------------------
*/

if ( $res_cd == "0000" )
{
    if ( $bSucc == "false")
    {
        $res_data      = "";
        $req_data      = "";
        $kcp_sign_data = "";
        /* 
        ==========================================================================
        취소 API URL                                                           
        --------------------------------------------------------------------------
        */
        $target_URL = "https://stg-spl.kcp.co.kr/gw/mod/v1/cancel"; // 개발서버
        //$target_URL = "https://spl.kcp.co.kr/gw/mod/v1/cancel"; // 운영서버
        
        // 서명데이터생성에시
        // site_cd(사이트코드) + "^" + tno(거래번호) + "^" + mod_type(취소유형)
        // NHN KCP로부터 발급받은 개인키(PRIVATE KEY)로 SHA256withRSA 알고리즘을 사용한 문자열 인코딩 값
        $cancel_target_data = $site_cd . "^" . $tno . "^" . "STSC";
        /*
            ==========================================================================
            privatekey 파일 read
            --------------------------------------------------------------------------
            */
        $key_data = file_get_contents(G5_PATH . "/bbs/kcp-batch/certificate/splPrikeyPKCS8.pem");
        
        /*
            ==========================================================================
            privatekey 추출
            'changeit' 은 테스트용 개인키비밀번호
            --------------------------------------------------------------------------
            */
        $pri_key = openssl_pkey_get_private($key_data,'changeit');
        
        /*
            ==========================================================================
            sign data 생성
            --------------------------------------------------------------------------
            */
        // 결제 취소 signature 생성
        openssl_sign($cancel_target_data, $signature, $pri_key, 'sha256WithRSAEncryption');
        //echo "cancel_signature :".base64_encode($signature)."<br><br>";
        $kcp_sign_data = base64_encode($signature);
        
        $data = array(
            "site_cd"        => $site_cd,
            "kcp_cert_info"  => $kcp_cert_info,
            "kcp_sign_data"  => $kcp_sign_data,
            "tno"            => $tno,
            "mod_type"       => "STSC",
            "mod_desc"       => "가맹점 DB 처리 실패(자동취소)"
        );
        
        $req_data = json_encode($data);
        
        $header_data = array( "Content-Type: application/json", "charset=utf-8" );
        
        // API REQ
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        // API RES
        $res_data  = curl_exec($ch);
        
        // RES JSON DATA Parsing
        $json_res = json_decode($res_data, true);
        
        $res_cd  = $json_res["res_cd" ] = "9999";//$json_res["res_cd" ];
        $res_msg = $json_res["res_msg"] = "DB 입력오류로 인한 결제취소처리";//$json_res["res_msg"];

        curl_close($ch);
    }
}

// 결과 출력
if (version_compare(phpversion(), "5.4", ">=")) {
    echo json_encode($json_res, JSON_UNESCAPED_UNICODE);
} else {
    function han ($s) { return reset(json_decode('{"s":"'.$s.'"}')); }
    function to_han ($str) { return preg_replace('/(\\\u[a-f0-9]+)+/e', 'han("$0")', $str); }

    echo to_han(json_encode($json_res));
}
exit;
