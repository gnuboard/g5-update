<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 테스트 결제 자동 취소
if($od_pg == 'KAKAOPAY' && $default['de_card_test'] && $tno) {
    $_REQUEST['TID']               = $tno;
    $_REQUEST['Amt']               = $amount;
    $_REQUEST['CancelMsg']         = '데모 테스트 결제 자동취소';
    $_REQUEST['PartialCancelCode'] = 0;

    include G5_SHOP_PATH.'/kakaopay/kakaopay_cancel.php';
}
?>