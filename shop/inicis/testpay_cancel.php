<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 테스트 결제 자동 취소
if($default['de_pg_service'] == 'inicis' && $default['de_card_test'] && $tno) {
    include_once(G5_SHOP_PATH.'/settle_inicis.inc.php');

    $cancel_msg = iconv_euckr('테스트 결제 자동 취소');

    /*********************
     * 3. 취소 정보 설정 *
     *********************/
    $inipay->SetField("type",      "cancel");                        // 고정 (절대 수정 불가)
    $inipay->SetField("mid",       $default['de_inicis_mid']);       // 상점아이디
    /**************************************************************************************************
     * admin 은 키패스워드 변수명입니다. 수정하시면 안됩니다. 1111의 부분만 수정해서 사용하시기 바랍니다.
     * 키패스워드는 상점관리자 페이지(https://iniweb.inicis.com)의 비밀번호가 아닙니다. 주의해 주시기 바랍니다.
     * 키패스워드는 숫자 4자리로만 구성됩니다. 이 값은 키파일 발급시 결정됩니다.
     * 키패스워드 값을 확인하시려면 상점측에 발급된 키파일 안의 readme.txt 파일을 참조해 주십시오.
     **************************************************************************************************/
    $inipay->SetField("admin",     $default['de_inicis_admin_key']); //비대칭 사용키 키패스워드
    $inipay->SetField("tid",       $tno);                            // 취소할 거래의 거래아이디
    $inipay->SetField("cancelmsg", $cancel_msg);                     // 취소사유

    /****************
     * 4. 취소 요청 *
     ****************/
    $inipay->startAction();
}
?>