<?php
require_once './_common.php';
require_once (G5_BBS_PATH . '/subscription/subscription_service.php');
require_once (G5_BBS_PATH . '/kcp-batch/KcpBatch.php');
$input_data = json_decode(file_get_contents('php://input'), true);

/**
 * @param string $w working mode 작업모드.
 */
$work = isset($input_data['w']) ? $input_data['w'] : '';

//필수 파라미터
if (empty($input_data) || $is_guest === true || empty($work)) {
    responseJson('잘못된 요청입니다.', 400);
}

if ($work === 'get_info') {
    $service_id = isset($input_data['service_id']) ? $input_data['service_id'] : '';
    $order_id = isset($input_data['order_id']) ? $input_data['order_id'] : '';

    //유효성 검사
    if (empty($service_id) || empty($order_id)) {
        responseJson('잘못된 요청입니다.', 400);
        exit;
    }

    send_batch_info($order_id, $service_id);
}


/**
 * @param $order_id
 * @param $service_id
 * @return void
 */
function send_batch_info($order_id, $service_id)
{
    $result = get_batchkey_info_kcp($order_id, $service_id);
    if ($result === false) {
        responseJson('결제정보를 가져오는데 실패했습니다.', 400);
    }
    echo json_encode($result);
}

/**
 * PG 사에 배치키 발급 요청정보를 설정 PC.
 * @param $order_id
 * @param $service_id
 * @return array | false
 */
function get_batchkey_info_kcp($order_id, $service_id)
{
    $info = showServiceDetail($service_id);
    if (is_array($info) && count($info) !== 1) {
        return false;
    }

    $kcp_batch = new KcpBatch();

    $recurring_count = $info[0]['recurring_count'];
    $recurring_unit = $info[0]['recurring_unit'];
    if($recurring_unit === 'w'){
        $recurring_count *= 7;
        $recurring_unit = 'd';
    }
    $good_expr =  '2:' . $recurring_count . $recurring_unit;
    $sendInfo = array();
    $sendInfo['site_cd'] = $kcp_batch->getSiteCd();
    $sendInfo['ordr_idxx'] = $order_id;
    $sendInfo['kcpgroup_id'] = $kcp_batch->getKcpGroupId();
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