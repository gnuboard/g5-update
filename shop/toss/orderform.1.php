<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 전자결제를 사용할 때만 실행
if($default['de_iche_use'] || $default['de_vbank_use'] || $default['de_hp_use'] || $default['de_card_use'] || $default['de_easy_pay_use']) {
?>
<script src="https://js.tosspayments.com/v1"></script>
<script>
    // 테스트 결제시
    <?php if ($default['de_card_test']) { ?>
        var clientKey = 'test_ck_D5GePWvyJnrK0W0k6q8gLzN97Eoq'
    // 실 결제시
    <?php } else { ?>
        
    <?php } ?>
    var tossPayments = TossPayments(clientKey)

    // * 계좌이체 결제창 열기
    // tossPayments.requestPayment("계좌이체", {});

    tossPayments.requestPayment('카드', { // 결제 수단 파라미터
        // 결제 정보 파라미터
        amount: 15000,
        orderId: '2f1pSaxo0kXpsebQVHtzF',
        orderName: '토스 티셔츠 외 2건',
        customerName: '박토스',
        successUrl: 'http://localhost:8080/success',
        failUrl: 'http://localhost:8080/fail',
    })

</script>
<?php 
    }
