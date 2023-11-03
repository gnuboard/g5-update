<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

if($od['od_pg'] != 'nicepay') return;

include_once(G5_SHOP_PATH.'/settle_nicepay.inc.php');

$od_id = $od['od_id'];
$tno = $od['od_tno'];
$partialCancelCode = 1;
$cancel_msg = $mod_memo;    //취소사유
$cancelAmt = (int)$tax_mny + (int)$free_mny;

include G5_SHOP_PATH.'/nicepay/cancel_process.php';

$pg_res_cd = '';
$pg_res_msg = '';
$is_save_history = true;

if (isset($result['ResultCode'])) {
    // nicepay 의 경우 
    if ($result['ResultCode'] === '2001') {

        if ($is_save_history) {
            // 관리자 부분취소 로그
            $mod_history = G5_TIME_YMDHIS.' '.$member['mb_id'].' 부분취소 ('.$cancelAmt.') 처리, 잔액 ('.$result['RemainAmt'].")\n";
            $sql = " update {$g5['g5_shop_order_table']} set od_mod_history = CONCAT(od_mod_history,'$mod_history') where od_id = '$od_id' ";
            sql_query($sql, false);
        }

    } else {
        $pg_res_cd = $result['ResultCode'];
        $pg_res_msg = $result['ResultMsg'];
    }
} else {
    $pg_res_cd = '';
    $pg_res_msg = 'curl 로 데이터를 받지 못했습니다.';
}

if ($pg_res_msg) {
    alert('결제 부분취소 요청이 실패하였습니다.\\n\\n'.$pg_res_cd.' : '.$pg_res_msg);
}