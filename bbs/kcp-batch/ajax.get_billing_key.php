<?php
header('Content-type: application/json; charset=utf-8');

$pg_code = 'kcp';
require_once dirname(__FILE__) . '/_common.php';
require_once G5_BBS_PATH . '/subscription/subscription_service.php';
require_once G5_LIB_PATH . "/billing/{$pg_code}/config.php";
require_once G5_LIB_PATH . '/billing/G5AutoLoader.php';
$autoload = new G5AutoLoader();
$autoload->register();

$billing = new Billing($pg_code);
$key_history_model = new BillingKeyHistoryModel();
/* ===================================================== */
/* =  요청정보                                          = */
/* = ------------------------------------------------- = */
// 필수 파라미터
$tran_cd = isset($_POST['tran_cd']) ? $tran_cd : '';   // 요청코드
$enc_data = isset($_POST['enc_data']) ? $enc_data : ''; // 암호화 데이터
$enc_info = isset($_POST['enc_info']) ? $enc_info : ''; // 결제창 인증결과 암호화 정보
$od_id = isset($_POST['ordr_idxx']) ? $ordr_idxx : '';
$card_no = isset($res_data['$card_mask_no']) ? $res_data['$card_mask_no'] : ''; //batch_cardno_return_yn 설정시
// 인증서 정보(직렬화)
$site_cd = $billing->pg->getSiteCd();

if (empty($tran_cd) || empty($enc_data) || empty($enc_info)) {
    responseJson('필수 파라미터가 없습니다.', 400);
}

if ($site_cd !== trim($_POST['site_cd'])) {
    responseJson('파라미터가 유효하지 않습니다.', 400);
}

/* ====================================================  */
/* =  배치키 발급 요청                                   = */
/* = ------------------------------------------------- = */
$billing_key_req_data = $billing->pg->requestIssueBillKey(array(
    "enc_data" => $enc_data,
    "enc_info" => $enc_info,
    "tran_cd"  => $tran_cd
));

/* ====================================================  */
/* =  응답정보                                          = */
/* = ------------------------------------------------- = */
$res_data = $billing->convertPgDataToCommonData($billing_key_req_data);
if (isset($res_data['http_code'])) {
    responseJson($res_data['result_message'], $res_data['http_code']);
}

/* ====================================================  */
/* =   결과처리 및 반환                                 = */
/* = ------------------------------------------------- = */
// 로그 테이블 저장
$res_data['pg_code'] = $pg_code;
$res_data['od_id'] = $od_id;
$res_data['card_no'] = $card_no;
$res_data['mb_id'] = get_user_id();
$key_history_model->insert($res_data);

// 결과 출력
if($res_data['result_code'] === '0000') {
    echo json_encode(array(
            'result_code' => $res_data['result_code'],
            'billing_key' => $res_data['billing_key'],
            'enc_info' => $enc_info,
            'tran_cd' => $tran_cd
        )
    );
    exit;
}

// 나머지 결과 출력
if(isset($res_data['result_code'])){
    responseJson("result_code: {$res_data['result_code']}  {$res_data['result_message']}", 400);
} else {
    responseJson($res_data['result_message'], $res_data['http_code']);
}
