<?php
include_once './_common.php';
include_once '../settle_toss.inc.php';

$isSuccess = false;

// 가상계좌 결제 데이터
$postData = file_get_contents('php://input');
$json = json_decode($postData);

$secret         = $json->secret;
$status         = $json->status;
$orderId        = $json->orderId;
$transactionKey = $json->transactionKey;
$createdAt      = $json->createdAt;

/*
secret: 가상계좌 웹훅 요청이 정상적인 요청인지 검증하기 위한 값입니다. 이 값이 결제 승인 API의 응답으로 돌아온 secret과 같으면 정상적인 요청입니다.
status: 입금 처리 상태입니다. 고객이 가상계좌에 입금하면 값이 DONE입니다. 입금이 취소되면 값이 CANCELED 입니다.
    DONE : 가상계좌에 입금되었습니다.
    CANCELED : 가상계좌 입금이 취소되었습니다.
    PARTIAL_CANCELED : 가상계좌 입금 부분 취소가 이루어졌습니다.
transactionKey: 가상계좌 입금 완료 및 취소에 대한 거래를 특정하는 키입니다.
orderId: 상점에서 주문 건을 구분하기 위해 발급한 고유 ID입니다.
{
  "createdAt": "2022-06-09T15:40:09+09:00",
  "secret": "yb5cXfHeXDpGPYSM61r5G",
  "status": "DONE",
  "transactionKey": "13CD6AE82C268B57F4E7FB976DC45475",
  "orderId": "4SUKlt-wk5YiKnXGjFkC4"
}
*/

// 주문번호 체크
if (!$orderId) {
    alert("주문번호가 없습니다.");
}

// 주문데이터 체크
$orderInfo = sql_fetch("SELECT * FROM {$g5['g5_shop_order_table']} WHERE od_id = '{$orderId}' ");
if (empty($orderInfo)) {
    alert("주문정보가 저장되지 않았습니다.");
}

/**
 * secret 값 비교 
 */ 
if ($secret == $orderInfo['od_app_no']) {
    $isSuccess = true;
}

/**
 * 결제데이터 처리 및 결과 전송
 */
if ($isSuccess) {
    // 정상처리
    if ($status == "DONE") {
        $ct_status = '입금';

        // 히스토리에 남김 (작업|아이디|시간|IP|나머지 자료)
        $now = G5_TIME_YMDHIS;
        $ct_history="\n$ct_status|virtualAccountCallback|$now|$REMOTE_ADDR";

        $sql = "UPDATE {$g5['g5_shop_cart_table']}
                    SET ct_status     = '$ct_status',
                        ct_history    = CONCAT(ct_history, '$ct_history')
                    WHERE od_id = '$orderId'";
        sql_query($sql);

        $price = (int)$orderInfo['od_misu'];
        $sql = "UPDATE {$g5['g5_shop_order_table']}
                    SET 
                        od_status = '{$ct_status}',
                        od_misu = 0,
                        od_receipt_price = {$price},
                        od_receipt_time = now()
                WHERE od_id = '$orderId'";
        sql_query($sql);

    // 고객 계좌 송금 한도가 초과되었거나 네트워크 이슈 등으로 입금이 취소되어 요청이 재전송 된 경우
    } else if ($status == "WAITING_FOR_DEPOSIT") {
        $ct_status = '주문';

        // 히스토리에 남김 (작업|아이디|시간|IP|나머지 자료)
        $now = G5_TIME_YMDHIS;
        $ct_history="\n$ct_status|virtualAccountCallback|$now|$REMOTE_ADDR";

        $sql = "UPDATE {$g5['g5_shop_cart_table']}
                    SET ct_status     = '$ct_status',
                        ct_history    = CONCAT(ct_history, '$ct_history')
                    WHERE od_id = '$orderId'";
        sql_query($sql);


        $price = (int)$orderInfo['od_receipt_price'];
        $sql = "UPDATE {$g5['g5_shop_order_table']}
                    SET 
                        od_status = '{$ct_status}',
                        od_misu = {$price},
                        od_receipt_price = 0,
                        od_receipt_time = '0000-00-00 00:00:00'
                WHERE od_id = '$orderId'";
        sql_query($sql);
    }
}