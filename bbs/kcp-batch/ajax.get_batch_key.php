<?php
header("Content-type: text/html; charset=utf-8");

include_once('./_common.php');

/* ============================================================================== */
/* =   API URL                                                                  = */
/* = -------------------------------------------------------------------------- = */
$target_URL = "https://stg-spl.kcp.co.kr/gw/enc/v1/payment"; // 개발서버
//$target_URL = "https://spl.kcp.co.kr/gw/enc/v1/payment"; // 운영서버
/* ============================================================================== */
/* =  요청정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
$tran_cd            = $_POST["tran_cd"]; // 요청코드
$site_cd            = $_POST["site_cd"]; // 사이트코드
$site_key             = "";
// 인증서 정보(직렬화)
$filePath       = G5_PATH . "/bbs/kcp-batch/certificate/splCert.pem";
$kcp_cert_info  = str_replace("\n", "", file_get_contents($filePath));
// $kcp_cert_info      = "-----BEGIN CERTIFICATE-----MIIDgTCCAmmgAwIBAgIHBy4lYNG7ojANBgkqhkiG9w0BAQsFADBzMQswCQYDVQQGEwJLUjEOMAwGA1UECAwFU2VvdWwxEDAOBgNVBAcMB0d1cm8tZ3UxFTATBgNVBAoMDE5ITktDUCBDb3JwLjETMBEGA1UECwwKSVQgQ2VudGVyLjEWMBQGA1UEAwwNc3BsLmtjcC5jby5rcjAeFw0yMTA2MjkwMDM0MzdaFw0yNjA2MjgwMDM0MzdaMHAxCzAJBgNVBAYTAktSMQ4wDAYDVQQIDAVTZW91bDEQMA4GA1UEBwwHR3Vyby1ndTERMA8GA1UECgwITG9jYWxXZWIxETAPBgNVBAsMCERFVlBHV0VCMRkwFwYDVQQDDBAyMDIxMDYyOTEwMDAwMDI0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAppkVQkU4SwNTYbIUaNDVhu2w1uvG4qip0U7h9n90cLfKymIRKDiebLhLIVFctuhTmgY7tkE7yQTNkD+jXHYufQ/qj06ukwf1BtqUVru9mqa7ysU298B6l9v0Fv8h3ztTYvfHEBmpB6AoZDBChMEua7Or/L3C2vYtU/6lWLjBT1xwXVLvNN/7XpQokuWq0rnjSRThcXrDpWMbqYYUt/CL7YHosfBazAXLoN5JvTd1O9C3FPxLxwcIAI9H8SbWIQKhap7JeA/IUP1Vk4K/o3Yiytl6Aqh3U1egHfEdWNqwpaiHPuM/jsDkVzuS9FV4RCdcBEsRPnAWHz10w8CX7e7zdwIDAQABox0wGzAOBgNVHQ8BAf8EBAMCB4AwCQYDVR0TBAIwADANBgkqhkiG9w0BAQsFAAOCAQEAg9lYy+dM/8Dnz4COc+XIjEwr4FeC9ExnWaaxH6GlWjJbB94O2L26arrjT2hGl9jUzwd+BdvTGdNCpEjOz3KEq8yJhcu5mFxMskLnHNo1lg5qtydIID6eSgew3vm6d7b3O6pYd+NHdHQsuMw5S5z1m+0TbBQkb6A9RKE1md5/Yw+NymDy+c4NaKsbxepw+HtSOnma/R7TErQ/8qVioIthEpwbqyjgIoGzgOdEFsF9mfkt/5k6rR0WX8xzcro5XSB3T+oecMS54j0+nHyoS96/llRLqFDBUfWn5Cay7pJNWXCnw4jIiBsTBa3q95RVRyMEcDgPwugMXPXGBwNoMOOpuQ==-----END CERTIFICATE-----";

$enc_data           = $_POST["enc_data"]; // 암호화 인증데이터
$enc_info           = $_POST["enc_info"]; // 암호화 인증데이터

$data = array(
    "tran_cd"        => $tran_cd,
    "site_cd"        => $site_cd,
    "site_key"       => $site_key,
    "kcp_cert_info"  => $kcp_cert_info,
    "enc_data"       => $enc_data,
    "enc_info"       => $enc_info
);
$req_data = json_encode($data);

$header_data = array("Content-Type: application/json", "charset=utf-8");

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
/* =  응답정보                                                                     = */
/* = -------------------------------------------------------------------------- = */
// 공통
$res_cd = "";
$res_msg = "";
$card_cd = "";
$card_name = "";
$batch_key = "";

// RES JSON DATA Parsing
$json_res   = json_decode($res_data, true);

$res_cd     = $json_res["res_cd"];
$res_msg    = $json_res["res_msg"];

if ($res_cd == "0000") {
    $batch_key  = $json_res["batch_key"];
    $card_cd    = $json_res["card_cd"];
    $card_name  = $json_res["card_name"];
}

curl_close($ch);

/* ============================================================================== */
/* =   결과처리 및 반환                                                          = */
/* ============================================================================== */
// 로그 테이블 저장
$g5["kcp_batch_key_log_table"] = "g5_kcp_batch_key_log";
$sql = "INSERT INTO {$g5["kcp_batch_key_log_table"]} SET 
                mb_id               = '{$member["mb_id"]}',
                res_cd              = '{$res_cd}',
                res_msg             = '{$res_msg}',
                card_cd             = '{$card_cd}',
                card_name           = '{$card_name}',
                batch_key           = '{$batch_key}',
                date                = '" . date("Y-m-d H:i:s") . "'
            ";
sql_query($sql);

// 결과 출력
echo $res_data;
exit;
