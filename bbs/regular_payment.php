<?php
include_once('./_common.php');
require_once (G5_BBS_PATH . '/subscription/subscription_service.php');
include_once(G5_PATH . '/head.php');

/**
 * @var string $service_id $_POST['service_id']
 */
if($is_guest) {
    alert('회원만 이용하실 수 있습니다.', G5_BBS_URL . '/login.php');
}
 if(isset($service_id) && $service_id === '0'){
    $serviceId = 0;
} else {
    $serviceId = empty($service_id)? null : $service_id;
    if($serviceId === null){
        alert('서비스 상품이 없습니다.', G5_URL);
    }
}

$serviceResult = showServiceDetail($serviceId);
$serviceResult = $serviceResult[0];
if($serviceResult === null){
    alert('해당 상품이 없습니다.', G5_URL);
}

$od_id = get_uniqid();

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

?>
<form name="form_batch_key" id="form_batch_key" method="post" enctype="multipart/form-data">
    <input type="hidden" name="od_id" value="<?php echo $od_id ?>" maxlength="40" />
    <!-- 가맹점 정보 설정-->
    <input type="hidden" name="site_name"  value="<?php echo $g5['title'] ?>" />
    <input type="hidden" name="site_cd"  value="<?php echo $g5['title'] ?>" />

    <!-- 주민번호 S / 사업자번호 C 선택 -->
    <input type='hidden' name='batch_soc_choice' value='S' />

    <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
    <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
    <input type='hidden' name='batch_cardno_return_yn'  value='L'>

    <div class="register">
        <div id="register_form" class="form_01">
            <div class="register_form_inner">
                <h2>자동결제 카드정보 입력</h2>
                <ul>
                    <li>
                        <label for="reg_mb_id">
                            이름 (필수)
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">회원(주문자) 이름 입니다.</span>
                        </label>
                        <input type="text" name="buyr_name" id="buyr_name" value="<?php echo $member['mb_name']?>" class="frm_input half_input" minlength="3" maxlength="20" placeholder="이름">
                        <button type="button" id="btn_batch_key" class="btn_frmline" >카드정보 입력</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</form>

<form id="form_payment" name="form_payment" method="post" enctype="multipart/form-data">
    <input type="hidden" name="od_id" class="w200" value="<?php echo $od_id ?>" maxlength="40" />
    <!-- 필수 항목 : 결제 금액/화폐단위 -->
    <input type="hidden" name="currency" value="410"  />
    <input type="hidden" name="service_id" value="<?php echo $serviceId ?>" />
    <input type="hidden" name='batch_key' id='input_batch_key' value='' />
    <input type="hidden" name="enc_info" value=""/>
    <input type="hidden" name="enc_data" value=""/>
    <input type="hidden" name="tran_cd" value=""/>
    <div class="register">
        <div id="register_form" class="form_01">
            <div class="register_form_inner">
                <h2>정기구독 상품정보</h2>
                <ul>
                    <li class="half_input left_input margin_input">
                        <label for="good_name">
                            상품명
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only"><?= $serviceResult['service_summary'] ?></span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" readonly name="good_name" id="good_name" value="<?= $serviceResult['service_name'] ?>" class="frm_input full_input" placeholder="<?= $serviceResult['service_name'] ?>">
                    </li>
                    <li class="half_input left_input">
                        <label for="good_mny">
                            상품금액
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="good_mny" id="good_mny" value="<?php echo $serviceResult['price']?>" class="frm_input full_input" placeholder="상품금액">
                    </li>
                    <li>
                        <label for="buyr_name">
                            주문자명
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">주문자명</span></button>
                            <span class="tooltip">주문자명</span>
                        </label>
                        <input type="text" name="buyr_name" id="buyr_name" value="<?php echo $member['mb_name']?>" class="frm_input half_input" placeholder="주문자명">
                    </li>
                    <li class="half_input left_input margin_input">
                        <label for="buyr_tel2">
                            휴대폰번호
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="buyr_tel2" id="buyr_tel2" value="010-0000-0000" class="frm_input full_input" placeholder="휴대폰번호">
                    </li>
                    <li class="half_input left_input">
                        <label for="buyr_mail">
                            이메일
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="buyr_mail" id="buyr_mail" value="test@test.co.kr" class="frm_input full_input" placeholder="이메일">
                    </li>
                    <button type="button" id="btn_payment" class="btn_frmline" >결제 요청</button>
                </ul>
            </div>
        </div>
    </div>
</form>

<script>
/**
 * KCP pc web 함수이며 함수명 변경금지.
 * 결제 인증후 리턴받는 재귀함수.
 * @param returnForm
 * @param closeEvent
 */

let site_cd = '';

function m_Completepayment(returnForm, closeEvent)
{
    if (returnForm.res_cd.value == "0000" ) {
        const batchForm = document.getElementById('form_batch_key');
        const paymenForm = document.getElementById('form_payment')
        const od_id = batchForm.od_id.value;
        const batch_soc_choice = batchForm.batch_soc_choice.value != undefined ? batchForm.batch_soc_choice.value : 'S';
        const batch_cardno_return_yn = batchForm.batch_cardno_return_yn.value != undefined ? batchForm.batch_cardno_return_yn.value : 'L';
        let kcpForm = new FormData(returnForm);
        kcpForm.append('od_id', od_id);
        kcpForm.append('batch_soc_choice', batch_soc_choice);
        kcpForm.append('site_cd', site_cd);

        const queryString = new URLSearchParams(kcpForm).toString();
        $.ajax({
            url : "kcp-batch/ajax.get_batch_key_class.php",
            type: "POST",
            data: queryString,
            success: function (data) {
                if (data) {
                    const result = JSON.parse(data);
                    if (result.res_cd == "0000") {
                        paymenForm.batch_key.value = result.batch_key;
                        paymenForm.enc_info.value = result.enc_info;
                        paymenForm.tran_cd.vaue = result.tran_cd;
                        alert('결제 정보가 정상적으로 입력되었습니다.');
                    } else {
                        alert('카드 등록에 실패했습니다.');
                        console.log("[" + result.res_cd + "]" + result.res_msg);
                    }

                } else {
                    alert("잠시 후에 시도해주세요.");
                }
            },
            error: function() {
                alert("에러 발생");
            }
        });

        closeEvent();
    }
    else
    {
        closeEvent();
    }
}

window.onload = function() {
    const form = document.querySelector("#form_batch_key");
    const payment_form = document.querySelector("#form_payment");
    const btn_batch_key = document.querySelector("#btn_batch_key");
    const btn_payment = document.querySelector("#btn_payment");

    if(payment_form.service_id == '' || payment_form.service_id == undefined) {
        alert('결제 창이 잘못되었습니다. 새로고침 바랍니다.')
    }
    /* 표준웹 실행 */
    btn_batch_key.onclick = function(){
        const serviceId = payment_form.service_id.value;
        loadSubscriptionInfo(serviceId);
    };

    function loadSubscriptionInfo(serviceId){
        const orderId = payment_form.od_id.value;
        let param = {
            'w' : 'getBatchInfo',
            'serviceId' : serviceId,
            'orderId' : orderId
        }
        $.ajax({
            url : 'kcp-batch/ajax.set_batch_info.php',
            type: 'POST',
            data: JSON.stringify(param),
            dataType : 'json',
            success: function(data) {
                if (data) {
                    let formTag = document.createElement('form');
                    formTag.setAttribute('method', 'post');
                    formTag.setAttribute('enctype', 'multipart/form-data');

                    for (let key in data) {
                        let input_tag = document.createElement('input');
                        input_tag.setAttribute('type', 'hidden');
                        input_tag.setAttribute('name', key);
                        input_tag.setAttribute('value', data[key]);
                        formTag.appendChild(input_tag);
                    }

                    //create form tag from data variable
                    console.log(formTag.site_cd.value);
                    console.log(formTag.ordr_idxx.value);
                    console.log(formTag.kcpgroup_id.value);
                    console.log(formTag.pay_method.value);
                    console.log(formTag.card_cert_type.value);
                    console.log(formTag.module_type.value);
                    console.log(formTag.card_cert_type.value);
                    console.log(formTag.batch_soc.value);
                    console.log(formTag.good_expr.value);
                    site_cd = formTag.site_cd.value;

                    try {
                        KCP_Pay_Execute(formTag);
                    } catch (e) {
                        /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
                    }
                } else {
                    alert("잠시 후에 시도해주세요.");
                }
            },
            error: function() {
                alert("결제 에러 발생");
            }
        });
    }

    // 결제 요청
    btn_payment.onclick = function(){
        const formData = new FormData(document.getElementById('form_payment'));
        const queryString = new URLSearchParams(formData).toString();
        if(formData.get('batch_key') == '' || formData.get('batch_key') == undefined) {
            alert('카드 등록을 먼저 진행해주세요.');
            return false;
        }

        $.ajax({
            url : "kcp-batch/ajax.order_batch_class.php",
            type: "POST",
            data: queryString,
            success: function(data) {
                if (data) {

                    let result = JSON.parse(data);
                    if (result.res_cd == "0000") {
                        // 성공
                        alert('결제가 완료되었습니다.');
                        window.location.replace(g5_url); //TODO 구독페이지 연결하기.
                    } else {
                        // 실패
                        alert('결제 요청에 실패했습니다.')
                    }
                } else {
                    alert("잠시 후에 시도해주세요.");
                }
            },
            error: function() {
                alert("결제 요청에 실패했습니다.");
            }
        });
    }
}

jQuery(function($){
	//tooltip
    $(document).on("click", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeIn(400).css("display","inline-block");
    }).on("mouseout", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeOut();
    });
});
</script>

<?php

include_once(G5_PATH . '/tail.php');
if (G5_DEBUG) {
    echo '<script type="text/javascript" src="https://testpay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
} else {
    echo '<script type="text/javascript" src="https://pay.kcp.co.kr/plugin/payplus_web.jsp"></script>';
}
