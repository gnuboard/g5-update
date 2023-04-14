<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 테스트 결제 자동 취소
if($default['de_pg_service'] == 'inicis' && $default['de_card_test'] && $tno) {
    include_once(G5_SHOP_PATH.'/settle_inicis.inc.php');

    $cancel_msg = '테스트 결제 자동 취소';

    $args = array(
        'paymethod' => get_type_inicis_paymethod($od_settle_case),
        'tid' => $tno,
        'msg' => $cancel_msg
    );

    $response = inicis_tid_cancel($args);
}