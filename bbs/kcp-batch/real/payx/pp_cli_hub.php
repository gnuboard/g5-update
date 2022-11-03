<?php 
    /* ============================================================================== */
    /* =   PAGE : 지불 요청 PAGE                                                    = */
    /* = -------------------------------------------------------------------------- = */
    /* =   Copyright (c)  2013   KCP Inc.   All Rights Reserved.                    = */
    /* ============================================================================== */
?>
<?php 
    /* ============================================================================== */
    /* = 라이브러리 및 사이트 정보 include                                          = */
    /* = -------------------------------------------------------------------------- = */

    require "./pp_cli_hub_lib.php";
    include "../../cfg/site_conf_inc.php";
    /* ============================================================================== */

    /* ============================================================================== */
    /* =   01. 지불 요청 정보 설정                                                  = */
    /* = -------------------------------------------------------------------------- = */
    $pay_method = $_POST[ "pay_method" ];  // 결제 방법
    $ordr_idxx  = $_POST[ "ordr_idxx"  ];  // 주문 번호
    $good_name  = $_POST[ "good_name"  ];  // 상품 정보
    $good_mny   = $_POST[ "good_mny"   ];  // 결제 금액
    $buyr_name  = $_POST[ "buyr_name"  ];  // 주문자 이름
    $buyr_mail  = $_POST[ "buyr_mail"  ];  // 주문자 E-Mail
    $buyr_tel1  = $_POST[ "buyr_tel1"  ];  // 주문자 전화번호
    $buyr_tel2  = $_POST[ "buyr_tel2"  ];  // 주문자 휴대폰번호
    $req_tx     = $_POST[ "req_tx"     ];  // 요청 종류
    $currency   = $_POST[ "currency"   ];  // 화폐단위 (WON/USD)
    /* = -------------------------------------------------------------------------- = */
    $mod_type      = $_POST[ "mod_type"     ];                         // 변경TYPE(승인취소시 필요)
    $mod_desc      = $_POST[ "mod_desc"     ];                         // 변경사유
    $amount        = "";                                               // 총 금액
    $panc_mod_mny  = "";                                               // 부분취소 요청금액
    $panc_rem_mny  = "";                                               // 부분취소 가능금액
    /* = -------------------------------------------------------------------------- = */
    $tran_cd       = "";                                               // 트랜잭션 코드
    $bSucc         = "";                                               // DB 작업 성공 여부
    /* = -------------------------------------------------------------------------- = */
    $res_cd        = "";                                               // 결과코드
    $res_msg       = "";                                               // 결과메시지
    $tno           = "";                                               // 거래번호
    /* = -------------------------------------------------------------------------- = */
    $card_pay_method = $_POST[ "card_pay_method" ];                    // 카드 결제 방법
    $card_cd         = "";                                             // 카드 코드
    $card_no         = "";                                             // 카드 번호
    $card_name       = "";                                             // 카드명
    $app_time        = "";                                             // 승인시간
    $app_no          = "";                                             // 승인번호
    $noinf           = "";                                             // 무이자여부
    $quota           = "";                                             // 할부개월
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   02. 인스턴스 생성 및 초기화                                              = */
    /* = -------------------------------------------------------------------------- = */

    $c_PayPlus  = new C_PAYPLUS_CLI;
    $c_PayPlus->mf_clear();
    
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   03. 처리 요청 정보 설정, 실행                                            = */
    /* = -------------------------------------------------------------------------- = */

    /* = -------------------------------------------------------------------------- = */
    /* =   03-1. 승인 요청                                                          = */
    /* = -------------------------------------------------------------------------- = */
    // 업체 환경 정보
    $cust_ip = getenv( "REMOTE_ADDR" ); // 요청 IP (옵션값)

    if ( $req_tx == "pay" )
    {
    $tran_cd = "00100000";

    $common_data_set = "";

    $common_data_set .= $c_PayPlus->mf_set_data_us( "amount",   $good_mny    );
    $common_data_set .= $c_PayPlus->mf_set_data_us( "currency", $currency    );
    $common_data_set .= $c_PayPlus->mf_set_data_us( "cust_ip",  $cust_ip );
    $common_data_set .= $c_PayPlus->mf_set_data_us( "escw_mod", "N"      );

    $c_PayPlus->mf_add_payx_data( "common", $common_data_set );

    // 주문 정보
    $c_PayPlus->mf_set_ordr_data( "ordr_idxx", $ordr_idxx );
    $c_PayPlus->mf_set_ordr_data( "good_name", $good_name );
    $c_PayPlus->mf_set_ordr_data( "good_mny",  $good_mny  );
    $c_PayPlus->mf_set_ordr_data( "buyr_name", $buyr_name );
    $c_PayPlus->mf_set_ordr_data( "buyr_tel1", $buyr_tel1 );
    $c_PayPlus->mf_set_ordr_data( "buyr_tel2", $buyr_tel2 );
    $c_PayPlus->mf_set_ordr_data( "buyr_mail", $buyr_mail );

        if ( $pay_method == "CARD" )
        {
            $card_data_set;

            $card_data_set .= $c_PayPlus->mf_set_data_us( "card_mny", $good_mny );        // 결제 금액

                if ( $card_pay_method == "Batch" )
                {
                    $card_data_set .= $c_PayPlus->mf_set_data_us( "card_tx_type",   "11511000" );
                    $card_data_set .= $c_PayPlus->mf_set_data_us( "quota",          $_POST[ "quotaopt"     ] );
                    $card_data_set .= $c_PayPlus->mf_set_data_us( "bt_group_id",    $_POST[ "bt_group_id"  ] );
                    $card_data_set .= $c_PayPlus->mf_set_data_us( "bt_batch_key",   $_POST[ "bt_batch_key" ] );
                }
            $c_PayPlus->mf_add_payx_data( "card", $card_data_set );
        }
    }

    /* = -------------------------------------------------------------------------- = */
    /* =   03-2. 취소/매입 요청                                                     = */
    /* = -------------------------------------------------------------------------- = */
        else if ( $req_tx == "mod" )
        {

            $tran_cd = "00200000";

            $c_PayPlus->mf_set_modx_data( "tno",      $_POST[ "tno" ]      );      // KCP 원거래 거래번호
            $c_PayPlus->mf_set_modx_data( "mod_type", $mod_type            );      // 원거래 변경 요청 종류
            $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip             );      // 변경 요청자 IP
            $c_PayPlus->mf_set_modx_data( "mod_desc", $_POST[ "mod_desc" ] );      // 변경 사유

            if ( $mod_type == "STPC" ) // 부분취소의 경우
            {
                $c_PayPlus->mf_set_modx_data( "mod_mny", $_POST[ "mod_mny" ] ); // 취소요청금액
                $c_PayPlus->mf_set_modx_data( "rem_mny", $_POST[ "rem_mny" ] ); // 취소가능잔액
            }
        }
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   03-3. 실행                                                               = */
    /* ------------------------------------------------------------------------------ */
        if ( $tran_cd != "" )
        {
            $c_PayPlus->mf_do_tx( $trace_no, $g_conf_home_dir, $g_conf_site_cd, "", $tran_cd, "",
                                  $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                                  $cust_ip, "3" , 0, 0, $g_conf_log_path ); // 응답 전문 처리

            $res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
            $res_msg = $c_PayPlus->m_res_msg; // 결과 메시지
        }
        else
        {
            $c_PayPlus->m_res_cd  = "9562";
            $c_PayPlus->m_res_msg = "연동 오류|Payplus Plugin이 설치되지 않았거나 tran_cd값이 설정되지 않았습니다.";
        }

    /* ============================================================================== */


    /* ============================================================================== */
    /* =   04. 승인 결과 처리                                                       = */
    /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            if ( $res_cd == "0000" )
            {
                $tno   = $c_PayPlus->mf_get_res_data( "tno"       ); // KCP 거래 고유 번호

    /* = -------------------------------------------------------------------------- = */
    /* =   04-1. 신용카드 승인 결과 처리                                            = */
    /* = -------------------------------------------------------------------------- = */
                if ( $pay_method == "CARD" )
                {
                    $card_cd   = $c_PayPlus->mf_get_res_data( "card_cd"   ); // 카드사 코드
                    $card_no   = $c_PayPlus->mf_get_res_data( "card_no"   ); // 카드 번호
                    $card_name = $c_PayPlus->mf_get_res_data( "card_name" ); // 카드 종류
                    $app_time  = $c_PayPlus->mf_get_res_data( "app_time"  ); // 승인 시간
                    $app_no    = $c_PayPlus->mf_get_res_data( "app_no"    ); // 승인 번호
                    $noinf     = $c_PayPlus->mf_get_res_data( "noinf"     ); // 무이자 여부 ( 'Y' : 무이자 )
                    $quota     = $c_PayPlus->mf_get_res_data( "quota"     ); // 할부 개월 수
                }

    /* = -------------------------------------------------------------------------- = */
    /* =   04-2. 승인 결과를 업체 자체적으로 DB 처리 작업하시는 부분입니다.         = */
    /* = -------------------------------------------------------------------------- = */
    /* =         승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해      = */
    /* =         DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로       = */
    /* =         승인 취소 요청을 하는 프로세스가 구성되어 있습니다.                = */
    /* =         DB 작업이 실패 한 경우, bSucc 라는 변수(String)의 값을 "false"     = */
    /* =         로 세팅해 주시기 바랍니다. (DB 작업 성공의 경우에는 "false" 이외의 = */
    /* =         값을 세팅하시면 됩니다.)                                           = */
    /* = -------------------------------------------------------------------------- = */
		    $bSucc = "";             // DB 작업 실패일 경우 "false" 로 세팅

    /* = -------------------------------------------------------------------------- = */
    /* =   04-3. DB 작업 실패일 경우 자동 승인 취소                                 = */
    /* = -------------------------------------------------------------------------- = */
            if ( $req_tx == "pay" )
            {
                if( $res_cd == "0000" )
                {
                    if ( $bSucc == "false" )
                    {
                        $c_PayPlus->mf_clear();

                        $tran_cd = "00200000";

                        $c_PayPlus->mf_set_modx_data( "tno",      $tno                         );  // KCP 원거래 거래번호
                        $c_PayPlus->mf_set_modx_data( "mod_type", "STSC"                       );  // 원거래 변경 요청 종류
                        $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip                     );  // 변경 요청자 IP (옵션값)
                        $c_PayPlus->mf_set_modx_data( "mod_desc", "결과 처리 오류 - 자동 취소" );  // 변경 사유

                         $c_PayPlus->mf_do_tx( $trace_no, $g_conf_home_dir, $g_conf_site_cd, "", $tran_cd, "",
                                  $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                                  $cust_ip, "3" , 0, 0, $g_conf_log_path ); // 응답 전문 처리

                        $res_cd  = $c_PayPlus->m_res_cd;
                        $res_msg = $c_PayPlus->m_res_msg;
                    }
                }
            } // End of [res_cd = "0000"]
            }
        }

    /* ============================================================================== */
    /* =   05. 취소/매입 결과 처리                                                  = */
    /* = -------------------------------------------------------------------------- = */
        else if ( $req_tx == "mod" )
        {
            if ( $res_cd == "0000" )
            {
                if ( $mod_type == "STPC" )
                {
                $amount       = $c_PayPlus->mf_get_res_data( "amount"       ); // 총 금액
                $panc_mod_mny = $c_PayPlus->mf_get_res_data( "panc_mod_mny" ); // 부분취소 요청금액
                $panc_rem_mny = $c_PayPlus->mf_get_res_data( "panc_rem_mny" ); // 부분취소 가능금액
                }
            }
        }
    /* ============================================================================== */

    /* ============================================================================== */
    /* =   06. 폼 구성 및 결과페이지 호출                                           = */
    /* ============================================================================== */
?>

    <html>
    <head>
        <script type="text/javascript">
            function goResult()
            {
                document.pay_info.submit();
            }
        </script>
    </head>

    <body onload="goResult();">
        <form name="pay_info" method="post" action="./result.php">
            <input type="hidden" name="req_tx"     value="<?php echo $req_tx     ?>">  <!-- 요청 구분 -->
            <input type="hidden" name="pay_method" value="<?php echo $pay_method ?>">  <!-- 사용한 결제 수단 -->
            <input type="hidden" name="bSucc"      value="<?php echo $bSucc      ?>">  <!-- 쇼핑몰 DB 처리 성공 여부 -->
            <input type="hidden" name="mod_type"   value="<?php echo $mod_type   ?>">
            <input type="hidden" name="amount"     value="<?php echo $amount     ?>">  <!-- 총 금액 -->
            <input type="hidden" name="panc_mod_mny"   value="<?php echo $panc_mod_mny?>">  <!-- 부분취소 요청금액 -->
            <input type="hidden" name="panc_rem_mny"   value="<?php echo $panc_rem_mny?>">  <!-- 부분취소 가능금액 -->

            <input type="hidden" name="res_cd"     value="<?php echo $res_cd     ?>">  <!-- 결과 코드 -->
            <input type="hidden" name="res_msg"    value="<?php echo iconv("EUC-KR", "UTF-8", $res_msg);    ?>">  <!-- 결과 메세지 -->
            <input type="hidden" name="ordr_idxx"  value="<?php echo $ordr_idxx  ?>">  <!-- 주문번호 -->
            <input type="hidden" name="tno"        value="<?php echo $tno        ?>">  <!-- KCP 거래번호 -->
            <input type="hidden" name="good_mny"   value="<?php echo $good_mny   ?>">  <!-- 결제금액 -->
            <input type="hidden" name="good_name"  value="<?php echo $good_name  ?>">  <!-- 상품명 -->
            <input type="hidden" name="buyr_name"  value="<?php echo $buyr_name  ?>">  <!-- 주문자명 -->
            <input type="hidden" name="buyr_tel1"  value="<?php echo $buyr_tel1  ?>">  <!-- 주문자 전화번호 -->
            <input type="hidden" name="buyr_tel2"  value="<?php echo $buyr_tel2  ?>">  <!-- 주문자 휴대폰번호 -->
            <input type="hidden" name="buyr_mail"  value="<?php echo $buyr_mail  ?>">  <!-- 주문자 E-mail -->

            <input type="hidden" name="card_cd"    value="<?php echo $card_cd    ?>">  <!-- 카드코드 -->
            <input type="hidden" name="card_no"    value="<?php echo $card_no    ?>">  <!-- 카드번호 -->
            <input type="hidden" name="card_name"  value="<?php echo iconv("EUC-KR", "UTF-8", $card_name);  ?>">  <!-- 카드명 -->
            <input type="hidden" name="app_time"   value="<?php echo $app_time   ?>">  <!-- 승인시간 -->
            <input type="hidden" name="app_no"     value="<?php echo $app_no     ?>">  <!-- 승인번호 -->
            <input type="hidden" name="quota"      value="<?php echo $quota      ?>">  <!-- 할부개월 -->
            <input type="hidden" name="noinf"      value="<?php echo $noinf      ?>">  <!-- 무이자여부 -->

        </form>
    </body>
    </html>
