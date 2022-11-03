<?php
include_once('./_common.php');

include_once(G5_PATH . '/head.php');

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

$ordr_idxx      = date('YmdHis') . rand(0, 6);
?>
<form name="form_batch_key" id="form_batch_key" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ordr_idxx" class="w200" value="<?php echo $ordr_idxx ?>" maxlength="40" />

    <!-- 가맹점 정보 설정-->
    <input type="hidden" name="site_name"      value="TEST SITE" />
    <!-- 상품제공기간 설정 -->
    <input type="hidden" name="good_expr"      value="2:1m"/>
    <!-- 결제 방법 : 인증키 요청-->
    <input type="hidden" name="pay_method"     value="AUTH:CARD" />
    <!-- 인증 방식 : 공동인증-->
    <input type="hidden" name="card_cert_type" value="BATCH" />
    <!-- 배치키 발급시 주민번호 입력을 결제창 안에서 진행 -->
    <input type='hidden' name='batch_soc'      value="Y"/>
    <!-- 
        ※필수 항목
        표준웹에서 값을 설정하는 부분으로 반드시 포함되어야 합니다값을 설정하지 마십시오
    -->
    <input type="hidden" name="module_type"     value="01"/>
    <input type="hidden" name="res_cd"          value=""/>
    <input type="hidden" name="res_msg"         value=""/>
    <input type="hidden" name="enc_info"        value=""/>
    <input type="hidden" name="enc_data"        value=""/>
    <input type="hidden" name="tran_cd"         value=""/>

    <!-- 주민번호 S / 사업자번호 C 픽스 여부 -->
    <!-- <input type='hidden' name='batch_soc_choice' value='' /> -->

    <!-- 배치키 발급시 카드번호 리턴 여부 설정 -->
    <!-- Y : 1234-4567-****-8910 형식, L : 8910 형식(카드번호 끝 4자리) -->
    <!-- <input type='hidden' name='batch_cardno_return_yn'  value='Y'> -->

    <!-- batch_cardno_return_yn 설정시 결제창에서 리턴 -->
    <!-- <input type='hidden' name='card_mask_no'			  value=''> -->

    <div class="register">
        <div id="register_form" class="form_01">
            <div class="register_form_inner">
                <h2>자동결제 키 발급</h2>
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
    <input type="hidden" name="ordr_idxx" class="w200" value="<?php echo $ordr_idxx ?>" maxlength="40" />
    <!-- 필수 항목 : 결제 금액/화폐단위 -->
    <input type="hidden" name="currency" value="410"  />

    <div class="register">
        <div id="register_form" class="form_01">
            <div class="register_form_inner">
                <h2>결제 요청</h2>
                <ul>
                    <li>
                        <label for="bt_batch_key">
                            자동결제 정보
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">상단 '자동결제 키 발급' 란에서 발급해주시기 바랍니다.</span>
                        </label>
                        <input type="text" name="bt_batch_key" id="bt_batch_key" value="" class="frm_input full_input" minlength="3" maxlength="20" placeholder="자동결제 배치 키">
                        <span class="complate_batch_key" style="color:red; display:none;">배치 키 발급 완료</span>
                    </li>
                    <li class="half_input left_input margin_input">
                        <label for="good_name">
                            상품명
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="good_name" id="good_name" value="test" class="frm_input full_input" placeholder="상품명">
                    </li>
                    <li class="half_input left_input">
                        <label for="good_mny">
                            상품금액
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="good_mny" id="good_mny" value="50000" class="frm_input full_input" placeholder="상품금액">
                    </li>
                    <li>
                        <label for="buyr_name">
                            주문자명
                            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
                            <span class="tooltip">주문 정보 입력</span>
                        </label>
                        <input type="text" name="buyr_name" id="buyr_name" value="홍길동" class="frm_input half_input" placeholder="주문자명">
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
<script type="text/javascript" src="https://testpay.kcp.co.kr/plugin/payplus_web.jsp"></script>
<script>
function m_Completepayment( frm_mpi, closeEvent ) 
{
    var frm = document.form_batch_key; 

    if (frm_mpi.res_cd.value == "0000" )
    {
        GetField(frm, frm_mpi); 
        
        let data = new FormData(document.getElementById('form_batch_key'));
        let queryString = new URLSearchParams(data).toString();

        $.ajax({
            url : "kcp-batch/ajax.get_batch_key_class.php",
            type: "POST",
            data: queryString,
            success: function(data) {
                if (data) {
                    console.log(data);
                    // Set Data
                    let result = JSON.parse(data);

                    if (result.res_cd == "0000") {
                        document.querySelector("#bt_batch_key").value = result.batch_key;
                        document.querySelector(".complate_batch_key").style.display = "block";
                    } else {
                        document.querySelector("#bt_batch_key").value = "";
                        document.querySelector(".complate_batch_key").style.display = "none";
                        alert("[" + result.res_cd + "]" + result.res_msg);
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

        setTimeout("alert( \"[" + frm_mpi.res_cd.value + "]" + frm_mpi.res_msg.value + "\");", 1000);
        
    }
}

function order_payment() {

}
window.onload = function() {
    let form            = document.querySelector("#form_batch_key");
    let btn_batch_key   = document.querySelector("#btn_batch_key");
    let btn_btn_payment = document.querySelector("#btn_payment");

    /* 표준웹 실행 */
    btn_batch_key.onclick = function(){
        try {
            KCP_Pay_Execute(form);
        } catch (e) {
            /* IE 에서 결제 정상종료시 throw로 스크립트 종료 */
        }
    };

    btn_btn_payment.onclick = function(){

        if (document.getElementById("bt_batch_key").value == "") {
            alert("자동결제 배치 키 발급 후 결제해주시기 바랍니다.");
            return false;
        }

        let data = new FormData(document.getElementById('form_payment'));
        let queryString = new URLSearchParams(data).toString();

        $.ajax({
            url : "kcp-batch/ajax.order_batch_class.php",
            type: "POST",
            data: queryString,
            success: function(data) {
                if (data) {
                    console.log(data);
                    // Set Data
                    let result = JSON.parse(data);
                    if (result.res_cd == "0000") {
                        // 성공
                        alert(result.res_msg);
                    } else {
                        // 실패
                        alert("[" + result.res_cd + "]" + result.res_msg);
                    }
                } else {
                    alert("잠시 후에 시도해주세요.");
                }
            },
            error: function() {
                alert("에러 발생");
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

