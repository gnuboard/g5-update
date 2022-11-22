<?php
require_once './_common.php';
require_once (G5_BBS_PATH . '/subscription/subscription_service.php');
require_once (G5_BBS_PATH . '/kcp-batch/KcpBatch.php');
$input_data = json_decode(file_get_contents('php://input'), true);

/**
 * @param string $w working mode 작업모드.
 */
$w = isset($input_data['w']) ? $input_data['w'] : '';

//필수 파라미터
if (empty($input_data) || $is_guest === true || empty($w)) {
    responseJson('잘못된 요청입니다.', 400);
}

if ($w === 'getBatchInfo') {
    $serviceId = isset($input_data['serviceId']) ? $input_data['serviceId'] : '';
    $orderId = isset($input_data['orderId']) ? $input_data['orderId'] : '';

    //유효성 검사
    if (empty($serviceId) || empty($orderId)) {
        responseJson('잘못된 요청입니다.', 400);
        exit;
    }

    sendBatchInfo($orderId, $serviceId);
}

function sendBatchInfo($orderId, $serviceId)
{
    $result = getBatchKeyInfoKcp($orderId, $serviceId);
    if ($result === false) {
        responseJson('결제정보를 가져오는데 실패했습니다.', 400);
    }
    echo json_encode($result);
}

/**
 * PG 사에 배치키 발급 요청정보를 설정 PC.
 * @param $serviceId
 * @return array | false
 */
function getBatchKeyInfoKcp($orderId, $serviceId)
{
    $info = showServiceDetail($serviceId);
    if (is_array($info) && count($info) !== 1) {
        return false;
    }

    $serviceExpiration = $info[0]['service_expiration'];
    $serviceUnit = $info[0]['recurring_unit'];
    if($serviceUnit === 'w'){
        $serviceExpiration *= 7;
        $serviceUnit = 'd';
    }
    $good_expr =  '2:' . $serviceExpiration . $serviceUnit;

    $kcpBatch = new KcpBatch();
    $sendInfo = array();
    $sendInfo['site_cd'] = $kcpBatch->getSiteCd();
    $sendInfo['ordr_idxx'] = $orderId;
    $sendInfo['kcpgroup_id'] = $kcpBatch->getKcpGroupId();
    $sendInfo['pay_method'] = 'AUTH:CARD';
    $sendInfo['card_cert_type'] = 'BATCH';
    $sendInfo['batch_soc'] = 'Y';
    $sendInfo['good_expr'] = $good_expr;
    $sendInfo['module_type'] = '01';
    $sendInfo['res_cd'] = '';
    $sendInfo['res_msg'] = '';
    $sendInfo['enc_info'] = '';
    $sendInfo['enc_data'] = '';
    $sendInfo['tran_cd'] = '';
    $sendInfo['batch_cardno_return_yn'] = 'L';
    $sendInfo['card_mask_no'] = '';

    return $sendInfo;
}