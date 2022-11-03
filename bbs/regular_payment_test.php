<?php
include_once('./_common.php');

include_once(G5_PATH . '/head.php');

include "./kcp-batch/cfg/site_conf_inc.php";

add_stylesheet('<link rel="stylesheet" media="all" id="cssLink" href="./kcp-batch/real/auth/css/style.css">', 10);
?>

<!--
    PAGE : INDEX PAGE
    Copyright (c)  2013   KCP Inc.   All Rights Reserverd.
//-->
<!-- <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=euc-kr" />
<meta name="viewport" content="width=device-width, user-scalable=1.0, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
</head> -->

<body>
    <!--  style="background:#0e66a4;" -->
    <link href="auth/css/style.css" rel="stylesheet">
    <div id="sample_index">
        <h1>PAYMENT SAMPLE</h1>
        <div class="btnSet">
            <a href="./kcp-batch/real/auth/request_key.php" class="btn1">&sdot; 배치키 발급 요청 <span>&rarr;</span></a>
            <a href="./kcp-batch/real/payx/order.php" class="btn2">&sdot; 배치키 결제 요청 <span>&rarr;</span></a>
            <a href="./kcp-batch/real/payx/cancel.php" class="btn3">&sdot; 취소 요청 <span>&rarr;</span></a>
        </div>
        <!--footer-->
        <div class="footer">
            Copyright (c) KCP INC. All Rights reserved.
        </div>
        <!--//footer-->
    </div>

    <form name="batch_key_form" id="batch_key_form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="ordr_idxx" class="w200" value="TEST<?php echo date('YmdHis') ?>" maxlength="40" />
        <input type="hidden" name="buyr_name" class="w100" value="홍길동" />
        <input type="hidden" name="kcpgroup_id" value="BA0011000348" class="w100" />
        <!-- 필수 항목 : 요청구분 -->
        <input type="hidden" name="req_tx" value="pay" />
        <input type="hidden" name="site_cd" value="<?php echo $g_conf_site_cd   ?>" />
        <input type="hidden" name="site_name" value="<?php echo $g_conf_site_name ?>" />

        <!-- 결제 방법 : 인증키 요청(AUTH:CARD) -->
        <input type='hidden' name='pay_method' value='AUTH:CARD'>

        <!-- 인증 방식 : 공인인증(BCERT) -->
        <input type='hidden' name='card_cert_type' value='BATCH'>

        <!-- 필수 항목 : PULGIN 설정 정보 변경하지 마세요 -->
        <input type='hidden' name='module_type' value='01'>

        <!-- 필수 항목 : PLUGIN에서 값을 설정하는 부분으로 반드시 포함되어야 합니다. ※수정하지 마십시오.-->
        <input type='hidden' name='res_cd' value=''>
        <input type='hidden' name='res_msg' value=''>
        <input type='hidden' name='trace_no' value=''>
        <input type='hidden' name='enc_info' value=''>
        <input type='hidden' name='enc_data' value=''>
        <input type='hidden' name='tran_cd' value=''>

        <!-- 배치키 발급시 주민번호 입력을 결제창 안에서 진행 -->
        <input type='hidden' name='batch_soc' value='Y'>

        <!-- 상품제공기간 설정 -->
        <input type='hidden' name='good_expr' value='2:1m'>

        <!-- 주민번호 S / 사업자번호 C 픽스 여부 -->
        <!-- <input type='hidden' name='batch_soc_choice' value='' /> -->

        <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
        <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
        <input type='hidden' name='batch_cardno_return_yn' value='Y'>

        <!-- batch_cardno_return_yn 설정시 결제창에서 리턴 -->
        <input type='hidden' name='card_mask_no' value=''>
    </form>
</body>

<script>
/****************************************************************/
/* m_Completepayment  설명                                      */
/****************************************************************/
/* 인증완료시 재귀 함수                                         */
/* 해당 함수명은 절대 변경하면 안됩니다.                        */
/* 해당 함수의 위치는 payplus.js 보다먼저 선언되어여 합니다.    */
/* Web 방식의 경우 리턴 값이 form 으로 넘어옴                   */
/* EXE 방식의 경우 리턴 값이 json 으로 넘어옴                   */
/****************************************************************/
function m_Completepayment(FormOrJson, closeEvent) {
    var frm = document.batch_key_form;

    /********************************************************************/
    /* FormOrJson은 가맹점 임의 활용 금지                               */
    /* frm 값에 FormOrJson 값이 설정 됨 frm 값으로 활용 하셔야 됩니다.  */
    /* FormOrJson 값을 활용 하시려면 기술지원팀으로 문의바랍니다.       */
    /********************************************************************/
    GetField(frm, FormOrJson);

    if (frm.res_cd.value == "0000") {
        /*
            가맹점 리턴값 처리 영역
        */
        let data = new FormData(document.getElementById('batch_key_form'));
        let queryString = new URLSearchParams(data).toString();

        $.ajax({
            url : "kcp-batch/ajax.get_batch_key.php",
            type: "POST",
            data: JSON.stringify(queryString),
            success: function(data) {
            if (data) {
                console.log(data);
                // Set Data
                let result = JSON.parse(data);
            } else {
                alert("잠시 후에 시도해주세요.");
            }
            },
            error: function() {
                alert("에러 발생");
            }
        });
    } else {
        alert("[" + frm.res_cd.value + "] " + frm.res_msg.value);
    }
    closeEvent();
}
</script>
<script type="text/javascript" src="<?php echo $g_conf_js_url ?>"></script>
<script>
/* Payplus Plug-in 실행 */
function jsf__pay(form) {
    try {
        KCP_Pay_Execute(form);
    } catch (e) {
        /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
    }
}
</script>
<?php
include_once(G5_PATH . '/tail.php');

