<?php
    /* ============================================================================== */
    /* =   PAGE : 결과 처리 PAGE                                                    = */
    /* = -------------------------------------------------------------------------- = */
    /* =   Copyright (c)  2013   KCP Inc.   All Rights Reserverd.                   = */
    /* ============================================================================== */

    
    /* ============================================================================== */
    /* =   01. 인증 결과                                                            = */
    /* = -------------------------------------------------------------------------- = */
    $res_cd      = $_POST[ "res_cd"      ];                // 결과 코드
    $res_msg     = $_POST[ "res_msg"     ];                // 결과 메시지
    /* = -------------------------------------------------------------------------- = */
    $ordr_idxx   = $_POST[ "ordr_idxx"   ];                // 주문번호
    $buyr_name   = $_POST[ "buyr_name"   ];                // 요청자 이름
    $card_cd     = $_POST[ "card_cd"     ];                // 카드 코드
    $card_mask_no= $_POST[ "card_mask_no"];                // 카드 번호
    $batch_key   = $_POST[ "batch_key"   ];                // 배치 인증키
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   02. 결과페이지 폼 구성                                                   = */
    /* ============================================================================== */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http:'www.w3.org/1999/xhtml" >

<head>
    <title>*** KCP Payment System ***</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html;" />
    <link href="css/style.css" rel="stylesheet" type="text/css" id="cssLink"/>
</head>

<body>
    <div id="sample_wrap">
    <form name="mod" method="post">
        <h1>[결과출력]<span> 이 페이지는 결제 결과를 출력하는 샘플(예시) 페이지입니다.</span></h1>
    <div class="sample">
        <p>
          요청 결과를 출력하는 페이지 입니다.<br />
          요청이 정상적으로 처리된 경우 결과코드(res_cd)값이 0000으로 표시됩니다.
        </p>
<?php
    /* ============================================================================== */
    /* =   결제 결과 코드 및 메시지 출력(결과페이지에 반드시 출력해주시기 바랍니다.)= */
    /* = -------------------------------------------------------------------------- = */
    /* =   결제 정상 : res_cd값이 0000으로 설정됩니다.                              = */
    /* =   결제 실패 : res_cd값이 0000이외의 값으로 설정됩니다.                     = */
    /* = -------------------------------------------------------------------------- = */
?>
                    <h2>&sdot; 처리 결과</h2>
                    <table class="tbl" cellpadding="0" cellspacing="0">
                        <!-- 결과 코드 -->
                        <tr>
                          <th>결과 코드</th>
                          <td><?php echo $res_cd?></td>
                        </tr>
                        <!-- 결과 메시지 -->
                        <tr>
                          <th>결과 메세지</th>
                          <td><?php echo $res_msg;?></td>
                        </tr>
                    </table>
<?php
            /* ============================================================================== */
            /* =   1. 정상 결제시 결제 결과 출력 ( res_cd값이 0000인 경우)                  = */
            /* = -------------------------------------------------------------------------- = */
            if ( $res_cd = "0000" )
            {
?>

                    <h2>&sdot; 주문 정보</h2>
                    <table class="tbl" cellpadding="0" cellspacing="0">
                    <!-- 주문번호 -->
                    <tr>
                        <th>주문번호</th>
                        <td><?php echo $ordr_idxx?></td>
                    </tr>
                    <!-- 주문자명 -->
                    <tr>
                        <th>주문자명</th>
                        <td><?php echo $buyr_name?></td>
                    </tr>
                    </table>

                    <h2>&sdot; 정기 과금 정보</h2>
                    <table class="tbl" cellpadding="0" cellspacing="0">
                    <!-- 결제 카드 -->
                    <tr>
                        <th>인증카드코드</th>
                        <td><?php echo $card_cd?></td>
                    </tr>
                    <tr>
                        <th>인증카드번호</th>
                        <td><?php echo $card_mask_no?></td>
                    </tr>
                    <!-- 승인시간 -->
                    <tr>
                        <th>배치키</th>
                        <td><?php echo $batch_key?></td>
                    </tr>
                    </table>
<?php
    }
?>
                    <!-- 처음으로 이미지 버튼 -->
                <tr>
                <div class="btnset">
                <a href="/g5-update/bbs/regular_payment.php" class="home">처음으로</a>
                </div>
                </tr>
              </tr>
            </div>
        <div class="footer">
                Copyright (c) KCP INC. All Rights reserved.
        </div>
    </div>
  </body>
</html>
