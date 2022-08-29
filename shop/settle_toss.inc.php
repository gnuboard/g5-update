<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>

<script>
    var tossParameter = {
        common : {
            clientKey : '<?php echo ($default['de_card_test'] ? 'test_ck_D5GePWvyJnrK0W0k6q8gLzN97Eoq' : ''); ?>', 

            /**
             * 결제가 성공하고 나면 리다이렉트(Redirect)되는 URL입니다.
             * 결제 승인 처리에 필요한 값들이 쿼리 파라미터(Query Parameter)로 함께 전달됩니다. 반드시 오리진(origin)을 포함해야 합니다.
             * 예를 들면 https://www.example.com/success와 같은 형태입니다.
             * 
             * @var string
             */
            successUrl : '<?php echo G5_SHOP_URL . '/toss/success.php'; ?>',

            /**
             * 결제가 실패하면 리다이렉트되는 URL입니다. 에러 코드 및 에러 메시지가 쿼리 파라미터로 함께 전송됩니다. 반드시 오리진(origin)을 포함해야 합니다.
             * 
             * @var string
             */
            failUrl : '<?php echo G5_SHOP_URL . '/toss/fail.php'; ?>',
            
            /**
             * 브라우저에서 결제창이 열리는 프레임을 지정합니다. self, iframe 중 하나입니다.
             * 값을 넣지 않으면 iframe에서 결제창이 열립니다. 현재창에서 결제창으로 이동시키는 방식을 사용하려면 값을 self로 지정하세요.
             * 모바일 웹에서는 windowTarget 값과 상관없이 항상 현재창에서 결제창으로 이동합니다.
             * 
             * @var string
             */
            windowTarget : 'iframe',

            /**
             * 결제할 금액 중 면세 금액입니다. 값을 넣지 않으면 기본값인 0으로 설정됩니다.
             * 면세 상점 혹은 복합 과세 상점일 때만 설정한 금액이 적용되고, 일반 과세 상점인 경우에는 적용되지 않습니다. 더 자세한 내용은 복합 과세 처리하기에서 살펴보세요.
             * 
             * @var int
             * @see https://docs.tosspayments.com/guides/tax
             */
            taxFreeAmount : 0,

            /**
             * 문화비로 지출했는지 여부입니다. (도서구입, 공연 티켓 박물관/미술관 입장권 등)
             * 
             * @var boolean
             */
            cultureExpense : false,
        },
        order : {
            amount : 0,         // 결제되는 금액입니다.
            orderId : '',       // 상점에서 주문 건을 구분하기 위해 발급한 고유 ID입니다. 영문 대소문자, 숫자, 특수문자 -, _, =로 이루어진 6자 이상 64자 이하의 문자열이어야 합니다.
            orderName : '',     // 결제에 대한 주문명입니다. 예를 들면 생수 외 1건 같은 형식입니다. 최대 길이는 100자입니다.
            customerName : '',  // 고객의 이름입니다. 최대 길이는 100자입니다.
            customerEmail : ''  // 고객의 이메일 주소입니다. 최대 길이는 100자입니다.
        },
        CARD : {
            /**
             * 카드사 코드입니다. 값이 있으면 카드사가 고정된 상태로 결제창이 열립니다.
             * 예를 들어, BC라는 값을 주면 BC카드로 고정된 결제창이 열립니다.
             * 
             * @var string
             * @see https://docs.tosspayments.com/reference/codes#%EC%B9%B4%EB%93%9C%EC%82%AC-%EC%BD%94%EB%93%9C
             */
            cardCompany : '',

            /**
             * 할부 개월 수를 고정해 결제창을 열 때 사용합니다. 결제 금액(amount)이 5만원 이상일 때만 사용할 수 있습니다.
             * 2부터 12사이의 값을 사용할 수 있고, 0이 들어가는 경우 할부가 아닌 일시불로 결제됩니다.
             * 값을 넣지 않으면 결제창에서 전체 할부 개월 수를 선택할 수 있습니다.
             * 
             * @var int
             */
            cardInstallmentPlan : 0,

            /**
             * 선택할 수 있는 최대 할부 개월 수를 제한하기 위해 사용합니다. 결제 금액(amount)이 5만원 이상일 때만 사용할 수 있습니다.
             * 2부터 12사이의 값을 사용할 수 있고, 0이 들어가는 경우 할부가 아닌 일시불로 결제됩니다. 만약 값을 6으로 설정한다면 결제창에서 일시불~6개월 사이로 할부 개월을 선택할 수 있습니다.
             * 할부 개월 수를 고정하는 cardInstallmentPlan와 같이 사용할 수 없습니다.
             * 
             * @var int
             */
            maxCardInstallmentPlan : 0,

            /**
             * 무이자 할부를 적용할 카드사 및 할부 개월 정보입니다. 이 파라미터에 포함된 정보와 고객이 선택한 카드사 및 할부 개월이 매칭되면 무이자 할부가 적용됩니다.
             * 이 파라미터를 통해 적용된 무이자 할부 비용은 상점이 부담합니다.
             * 
             * @var array
             * company 필수 string
             * - 할부를 적용할 카드사 코드입니다. 카드사 코드를 참조하세요.
             * months 필수 array
             * - 무이자를 적용할 할부 개월 정보입니다. 할부 개월을 배열에 추가해주세요.
             */
            freeInstallmentPlans : [
                {company : '', months : []}
            ],

            /**
             * 카드사 포인트를 사용했는지 여부입니다. 값을 주지 않으면 사용자가 카드사 포인트 사용 여부를 결정할 수 있습니다.
             * 이 값을 true로 주면 카드사 포인트 사용이 체크된 상태로 결제창이 열립니다. 이 값을 false로 주면 사용자는 카드사 포인트를 사용할 수 없습니다.
             * 
             * @var boolean
             */
            useCardPoint : false,

            /**
             * 카드사 앱카드로만 결제할지 여부를 결정합니다. 이 값을 true로 주면 카드사의 앱카드 결제창만 열립니다.
             * 카드사가 국민, 농협, 롯데, 삼성, 신한, 현대인 경우에만 true 값을 사용할 수 있습니다.
             * 
             * @var boolean
             */
            useAppCardOnly : false,

            /**
             * 해외카드(Visa, MasterCard, UnionPay)로 결제할 지 여부입니다. 값이 true면 해외카드 결제가 가능한 영문 결제창이 열립니다.
             * 
             * @var boolean
             */
            useInternationalCardOnly : false,

            /**
             * 값으로 DIRECT를 넣고 cardCompany 파라미터 값을 채우면 결제창의 약관 동의, 카드 선택 페이지를 건너뛰고 특정 카드사의 결제로 바로 연결됩니다.
             * DEFAULT, DIRECT 중 하나의 값이 들어올 수 있습니다.
             * 
             * @var string
             */
            flowMode : 'DEFAULT',

            /**
             * 간편결제 결제 수단 타입입니다. flowMode 값이 DIRECT여야 합니다. 간편결제 서비스 중 하나의 값이 들어올 수 있습니다.
             * 
             * @var string
             * @see https://docs.tosspayments.com/reference/enum-codes#%EA%B0%84%ED%8E%B8%EA%B2%B0%EC%A0%9C-%EC%84%9C%EB%B9%84%EC%8A%A4
             */
            easyPay : '',

            /**
             * 카드사의 할인 코드입니다. flowMode 값이 DIRECT여야 합니다. 카드 혜택 조회 API를 통해 적용할 수 있는 할인 코드의 목록을 조회할 수 있습니다.
             * 
             * @var string
             * @see https://docs.tosspayments.com/reference#%EC%B9%B4%EB%93%9C-%ED%98%9C%ED%83%9D-%EC%A1%B0%ED%9A%8C
             */
            discountCode : '',

            /**
             * 모바일 ISP 앱에서 상점 앱으로 돌아오기 위해 사용됩니다. 상점의 앱 스킴을 지정하면 됩니다. 예를 들면 testapp://같은 형태입니다.
             * 
             * @var string
             */
            appScheme : '',
        },
        TRANSFER : {
            /**
             * 현금영수증 발급 정보를 담는 객체입니다.
             * 
             * @var object
             */
            cashReceipt : {
                type : "미발행"     // 현금영수증 발급 용도입니다. 소득공제, 지출증빙, 미발행 중 하나입니다. 소득공제, 지출증빙 중 하나의 값을 넣으면 해당 용도가 선택된 상태로 결제창이 열립니다. 미발행을 넣으면 결제창에서 현금영수증 발급 용도를 선택할 수 없습니다.
            },

            /**
             * 에스크로 사용 여부입니다. 값을 주지 않으면 사용자가 에스크로 결제 여부를 선택합니다. 기본값은 false 입니다.
             *
             * @var boolean
             */
            useEscrow : false,

            /**
             * 각 상품에 대한 상세 정보를 담는 배열입니다.
             * 예를 들어 사용자가 세 가지 종류의 상품을 구매했다면 길이가 3인 배열이어야 합니다. 에스크로 결제를 사용할 때만 필요한 파라미터입니다.
             * 
             * @var object
             */
            escrowProducts : {
                id : "",            // 상품의 ID입니다. 이 값은 유니크해야 합니다.
                name : "",          // 상품 이름입니다
                code : "",          // 상점에서 사용하는 상품 관리 코드입니다.
                unitPrice : 0,      // 상품의 가격입니다. 전체를 합한 가격이 아닌 상품의 개당 가격인 점에 유의해주세요.
                quantity : 0,       // 상품 구매 수량입니다.
            }
        },
        VIRTUAL_ACCOUNT : {
            /**
             * 가상계좌가 유효한 시간을 의미합니다. 값을 넣지 않으면 기본값 168시간(7일)으로 설정됩니다. 설정할 수 있는 최대값은 720시간(30일)입니다.
             * validHours와 dueDate 중 하나만 사용할 수 있습니다.
             *
             * @var int
             */
            validHours : 0,

            /**
             * 입금 기한입니다. 현재 시간을 기준으로 720시간(30일) 이내의 특정 시점으로 입금 기한을 직접 설정하고 싶을 때 사용합니다. 720시간 이후로 기한을 설정하면 에러가 발생합니다.
             * ISO 8601 형식인 YYYY-MM-DDThh:mm:ss를 사용합니다.
             * validHours와 dueDate 중 하나만 사용해야 합니다.
             *
             * @var string
             */
            dueDate : '',

            /**
             * 가상계좌 웹훅 URL 주소입니다.
             *
             * @var string
             */
            virtualAccountCallbackUrl : '',

            /**
             * 고객의 휴대폰 번호입니다.
             *
             * @var string
             */
            customerMobilePhone : '',

            /**
             * 현금영수증 발급 정보를 담는 객체입니다.
             * 
             * @var object
             */
            cashReceipt : {
                type : "미발행"     // 현금영수증 발급 용도입니다. 소득공제, 지출증빙, 미발행 중 하나입니다. 소득공제, 지출증빙 중 하나의 값을 넣으면 해당 용도가 선택된 상태로 결제창이 열립니다. 미발행을 넣으면 결제창에서 현금영수증 발급 용도를 선택할 수 없습니다.
            },

            /**
             * 에스크로 사용 여부입니다. 값을 주지 않으면 사용자가 에스크로 결제 여부를 선택합니다. 기본값은 false 입니다.
             *
             * @var boolean
             */
            useEscrow : false,

            /**
             * 각 상품에 대한 상세 정보를 담는 배열입니다.
             * 예를 들어 사용자가 세 가지 종류의 상품을 구매했다면 길이가 3인 배열이어야 합니다. 에스크로 결제를 사용할 때만 필요한 파라미터입니다.
             * 
             * @var object
             */
            escrowProducts : {
                id : "",            // 상품의 ID입니다. 이 값은 유니크해야 합니다.
                name : "",          // 상품 이름입니다
                code : "",          // 상점에서 사용하는 상품 관리 코드입니다.
                unitPrice : 0,      // 상품의 가격입니다. 전체를 합한 가격이 아닌 상품의 개당 가격인 점에 유의해주세요.
                quantity : 0,       // 상품 구매 수량입니다.
            },

            /**
             * 결제할 때 사용할 통화 단위입니다. 값을 넣지 않으면 기본값인 KRW로 설정됩니다. 원화인 KRW만 사용합니다.
             *
             * @var string
             */
            currency : 'KRW',
        },
        MOBILE_PHONE : {
            /**
             * 결제창에서 선택할 수 있는 통신사를 제한하기 위해 사용할 수 있습니다. 배열에 통신사 코드를 추가하면 해당 통신사 코드만 선택할 수 있는 결제창이 뜹니다.
             * 값을 넣지 않으면 모든 통신사 코드를 선택할 수 있는 결제창이 뜹니다. 통신사 코드를 참고하세요.
             *
             * @var array
             * @see https://docs.tosspayments.com/reference/codes#%ED%86%B5%EC%8B%A0%EC%82%AC-%EC%BD%94%EB%93%9C
             */
            mobileCarrier : []
        }
    }
</script>