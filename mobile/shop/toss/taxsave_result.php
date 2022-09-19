<?php

/**
 * tosspayments 영수증 발급
 * @todo 현금영수증 발급 시, 응답 변수 중 receiptKey를 저장해야한다. (주문취소시 현금영수증도 취소할 때 사용). [g5_shop_order > od_casseqno]
 */
include_once('./_common.php');

require_once(G5_SHOP_PATH . '/settle_toss.inc.php');

$amount     = isset($_POST['amount']) ? clean_xss_tags($_POST['amount']) : 0;
$orderId    = isset($_POST['orderId']) ? safe_replace_regex($_POST['orderId'], 'od_id') : '';
$customerIdentityNumber = isset($_POST['customerIdentityNumber']) ? clean_xss_tags($_POST['customerIdentityNumber']) : 0;
$type       = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';
$tx         = isset($_POST['tx']) ? clean_xss_tags($_POST['tx'], 1, 1) : '';

if ($tx == 'personalpay') {
    $od = sql_fetch("SELECT * FROM {$g5['g5_shop_personalpay_table']} WHERE pp_id = '$orderId' ");
    if (!$od) {
        die('<p id="scash_empty">개인결제 내역이 존재하지 않습니다.</p>');
    }

    $od_tno      = $od['pp_tno'];
    $orderName   = $od['pp_name'] . '님 개인결제';
    $settle_case = $od['pp_settle_case'];
    $order_price = $od['pp_receipt_price'];
} else {
    $od = sql_fetch("SELECT * FROM {$g5['g5_shop_order_table']} WHERE od_id = '$orderId' ");
    if (!$od) {
        die('<p id="scash_empty">주문서가 존재하지 않습니다.</p>');
    }

    $od_tno      = $od['od_tno'];
    $goods       = get_goods($od['od_id']);
    $orderName   = $goods['full_name'];
    $settle_case = $od['od_settle_case'];
    $order_price = $od['od_tax_mny'] + $od['od_vat_mny'] + $od['od_free_mny'];
}

if (!in_array($settle_case, array('가상계좌', '계좌이체', '무통장'))) {
    die('<p id="scash_empty">현금영수증은 무통장, 가상계좌, 계좌이체에 한해 발급요청이 가능합니다.</p>');
}

$data = array(
    'orderId' => $orderId,
    'orderName' => $orderName,
    'amount' => $amount,
    'type' => $type,
    'customerIdentityNumber' => $customerIdentityNumber
);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.tosspayments.com/v1/cash-receipts",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic " . $credential,
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

$responseJson = json_decode($response);

if ($err) {
    alert($err);
} else {
    if (isset($responseJson->code)) {
        alert($responseJson->message);
    }

    // 결과 저장
    if ($tx == 'personalpay') {
        $sql = " update {$g5['g5_shop_personalpay_table']}
                    set pp_cash = '1',
                        pp_cash_no = '{$responseJson->issueNumber}',
                        pp_casseqno = '{$responseJson->receiptKey}',
                        pp_cash_info = '$response'
                  where pp_id = '$orderId' ";
    } else {
        $sql = " update {$g5['g5_shop_order_table']}
                    set od_cash = '1',
                        od_cash_no = '{$responseJson->issueNumber}',
                        od_casseqno = '{$responseJson->receiptKey}',
                        od_cash_info = '$response'
                  where od_id = '$orderId' ";
    }

    $result = sql_query($sql, false);

    // DB 정보갱신 실패시 취소
    if (!$result) {
        // 현금영수증 취소처리
        $receiptKey = $responseJson->receiptKey;
        include_once G5_SHOP_PATH . '/toss/cash_receipt_cancel.php';

        if ($receipt_result_msg) {
            alert_close($receipt_result_msg);
        }
    }
}

$g5['title'] = '';
include_once(G5_PATH . '/head.sub.php');
?>

<div id="lg_req_tx" class="new_win">
    <h1 id="win_title">현금영수증 - 토스페이먼츠 eCredit</h1>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col class="grid_4">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row">결과코드</th>
                    <td><?php echo $responseJson->issueStatus; ?></td>
                </tr>
                <tr>
                    <th scope="row">결과 메세지</th>
                    <td><?php echo $responseJson->transactionType; ?></td>
                </tr>
                <tr>
                    <th scope="row">현금영수증 거래번호</th>
                    <td><?php echo $responseJson->orderId; ?></td>
                </tr>
                <tr>
                    <th scope="row">현금영수증 승인번호</th>
                    <td><?php echo $responseJson->issueNumber; ?></td>
                </tr>
                <tr>
                    <th scope="row">승인시간</th>
                    <td><?php echo $responseJson->requestedAt; ?></td>
                </tr>
                <tr>
                    <th scope="row">현금영수증 URL</th>
                    <td>
                        <?php echo $responseJson->receiptUrl; ?>
                        <!-- <button type="button" name="receiptView" class="btn_frmline" onClick="javascript:showCashReceipts('<?php echo $LGD_MID; ?>','<?php echo $LGD_OID; ?>','<?php echo $od_casseqno; ?>','<?php echo $trade_type; ?>','<?php echo $CST_PLATFORM; ?>');">영수증 확인</button>
                <p>영수증 확인은 실 등록의 경우에만 가능합니다.</p> -->
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php
include_once(G5_PATH . '/tail.sub.php');
