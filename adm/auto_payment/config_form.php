<?php
$sub_menu = '800920';
require_once './_common.php';
require_once G5_EDITOR_LIB;

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '정기결제 설정';
require_once G5_ADMIN_PATH . '/admin.head.php';

$config_model  = new BillingConfigModel();
$billing_conf = $config_model->selectOne();
if (empty($billing_conf)) {
    $column_list = $config_model->selectColumnList();
    foreach ($column_list as $column) {
        $billing_conf[$column['column_name']] = ''; 
    }
}

// kcp의 경우 인증서 디렉토리 체크
if ($billing_conf['bc_pg_code'] == 'kcp') {
    $cert_path = G5_DATA_PATH . '/billing/kcp/certificate';

    if (!is_dir($cert_path)) {
        @mkdir($cert_path, G5_DIR_PERMISSION, true);
        @chmod($cert_path, G5_DIR_PERMISSION);

        if (!is_dir($cert_path)) {
            echo '<script>' . PHP_EOL;
            echo 'alert("' . str_replace(G5_PATH . '/', '', G5_DATA_PATH) . ' 폴더 안에 /billing/kcp/certificate 폴더를 생성하신 후 쓰기권한을 부여해 주십시오.\n> mkdir /billing/kcp/certificate\n> chmod 707 /billing/kcp/certificate");' . PHP_EOL;
            echo '</script>' . PHP_EOL;
        }
    }
}

$pg_anchor = '<ul class="anchor">
<li><a href="#anc_billing_config_service">구독서비스</a></li>
<li><a href="#anc_billing_config_payment">결제설정</a></li>
</ul>';

?>
<style>
.kcp_billing_file { display: inline-block; padding: 0px 10px; background: #226C8B; color: #fff; font-weight: normal; text-decoration: none; border-radius: 10px; }
</style>

<form name="fconfig" id="fconfig" action="./config_update.php" onsubmit="return fconfig_check(this)" method="post" enctype="multipart/form-data">
    <input type="hidden" name="token" value="">

    <section id="anc_billing_config_service">
        <h2 class="h2_frm">구독서비스 설정</h2>
        <?php echo $pg_anchor; ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>결제설정 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="bc_use_cancel_refund">구독취소 환불</label></th>
                        <td>
                            <?php echo help("사용자가 구독 취소 시, 남은 기간 만큼의 금액을 환산해서 결제 건에 대해서 부분취소 처리합니다.\n<b>`일별금액 * 잔여일`</b>로 환불가격이 계산됩니다.", 50); ?>
                            <select id="bc_use_cancel_refund" name="bc_use_cancel_refund">
                                <option value="0" <?php echo get_selected($billing_conf['bc_use_cancel_refund'], 0); ?>>사용안함</option>
                                <option value="1" <?php echo get_selected($billing_conf['bc_use_cancel_refund'], 1); ?>>사용</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_use_pause">구독 일시정지 (미구현)</label></th>
                        <td>
                            <?php echo help("구독 일시정지", 50); ?>
                            <select id="bc_use_pause" name="bc_use_pause">
                                <option value="0" <?php echo get_selected($billing_conf['bc_use_pause'], 0); ?>>사용안함</option>
                                <option value="1" <?php echo get_selected($billing_conf['bc_use_pause'], 1); ?>>사용</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="anc_billing_config_payment">
        <h2 class="h2_frm">결제설정</h2>
        <?php echo $pg_anchor; ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>결제설정 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="bc_pg_code">결제대행사</label></th>
                        <td>
                            <input type="hidden" name="bc_pg_code" id="bc_pg_code" value="<?php echo $billing_conf['bc_pg_code']; ?>">
                            <?php echo help('정기결제에서 사용할 결제대행사를 선택합니다.'); ?>
                            <ul class="de_pg_tab">
                                <li class="<?php echo ($billing_conf['bc_pg_code'] == 'kcp' ? 'tab-current' : '') ?>"><a href="#kcp_info_anchor" data-value="kcp" title="NHN KCP 선택하기">NHN KCP</a></li>
                                <li class="<?php echo ($billing_conf['bc_pg_code'] == 'toss' ? 'tab-current' : '') ?>"><a href="#toss_info_anchor" data-value="toss" title="토스페이먼츠 선택하기">토스페이먼츠</a></li>
                            </ul>
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld" id="kcp_info_anchor">
                        <th scope="row">
                            <label for="bc_kcp_site_cd">NHN KCP SITE CODE</label><br>
                            <a href="http://sir.kr/main/service/p_pg.php" target="_blank" class="kcp_btn">NHN KCP 신청하기</a>
                        </th>
                        <td>
                            <?php echo help("API를 연동하기 위해 NHN KCP에서 발급해드리는 site_cd가 필요합니다.
                                            코드 발급은 홈페이지 신규신청을 통한 접수 후 발급해주시기 바랍니다. 예) BA001 (테스트 코드)"); ?>
                            <input type="text" name="bc_kcp_site_cd" value="<?php echo get_sanitize_input($billing_conf['bc_kcp_site_cd']); ?>" id="bc_kcp_site_cd" class="frm_input code_input" size="10" maxlength="10">
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld">
                        <th scope="row"><label for="bc_kcp_group_id">NHN KCP 가맹점 GROUP ID</label></th>
                        <td>
                            <?php echo help("※ 자동결제 GROUP ID 생성 방법
                                            <a href='https://admin8.kcp.co.kr/assist/login.LoginAction.do' target='_blank'>NHN KCP 상점관리자 페이지 접속</a> → 결제 관리 → 일반결제 → 자동결제 → 그룹관리를 통해 그룹 아이디 생성. 예) BA0011000348 (테스트 ID)"); ?>
                            <input type="text" name="bc_kcp_group_id" value="<?php echo get_sanitize_input($billing_conf['bc_kcp_group_id']); ?>" id="bc_kcp_group_id" class="frm_input" size="20" maxlength="20">
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld">
                        <th scope="row"><label for="bc_kcp_cert">NHN KCP 서비스 인증서 파일</label></th>
                        <td>
                            <?php echo help("KCP-API 결제시 필요하고 각 상점에 맞는 인증서가 필요합니다.
                                            ※ 서비스 인증서 & 개인 키 생성 방법
                                            <a href='https://admin8.kcp.co.kr/assist/login.LoginAction.do' target='_blank'>NHN KCP 상점관리자 페이지 접속</a> → 고객센터 → 인증센터 → KCP PG-API → 발급하기 경로에서 개인키 + 인증서 발급이 가능합니다.
                                            ※ 테스트 서비스 인증서 값은 <a href='https://developer.kcp.co.kr/page/download' target='_blank'>다운로드 자료실</a>을 참고해주시기 바랍니다."); ?>
                            <input type="file" name="bc_kcp_cert">
                            <?php if (!empty($billing_conf['bc_kcp_cert'])) { ?>
                            <input type="hidden" name="bc_kcp_cert" value="<?php echo $billing_conf['bc_kcp_cert'] ?>">
                            <span class="kcp_billing_file"><?php echo $billing_conf['bc_kcp_cert'] ?> 업로드 완료</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld">
                        <th scope="row"><label for="bc_kcp_prikey">NHN KCP 개인 키 파일</label></th>
                        <td>
                            <?php echo help("가맹점 부인방지와 요청 데이터의 무결성 검증을 위한 데이터 파일입니다.
                                            ※ 서비스 인증서 & 개인 키 생성 방법
                                            <a href='https://admin8.kcp.co.kr/assist/login.LoginAction.do' target='_blank'>NHN KCP 상점관리자 페이지 접속</a> → 고객센터 → 인증센터 → KCP PG-API → 발급하기 경로에서 개인키 + 인증서 발급이 가능합니다.
                                            ※ 테스트 개인키는 <a href='https://developer.kcp.co.kr/page/download' target='_blank'>다운로드 자료실</a>을 확인해주세요."); ?>
                            <input type="file" name="bc_kcp_prikey">
                            <?php if (!empty($billing_conf['bc_kcp_prikey'])) { ?>
                            <input type="hidden" name="bc_kcp_prikey" value="<?php echo $billing_conf['bc_kcp_prikey'] ?>">
                            <span class="kcp_billing_file"><?php echo $billing_conf['bc_kcp_prikey'] ?> 업로드 완료</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld">
                        <th scope="row"><label for="bc_kcp_prikey_password">NHN KCP 개인 키 파일 암호</label></th>
                        <td>
                            <?php echo help("개인 키 파일을 생성할 때 사용한 암호를 입력해주세요. 예) changeit (테스트용 개인키 비밀번호)"); ?>
                            <input type="text" name="bc_kcp_prikey_password" value="<?php echo get_sanitize_input($billing_conf['bc_kcp_prikey_password']); ?>" id="bc_kcp_prikey_password" class="frm_input" size="36" maxlength="25">
                        </td>
                    </tr>
                    <tr class="pg_info_fld kcp_info_fld">
                        <th scope="row">결제 테스트</th>
                        <td>
                            <?php echo help("NHN KCP 결제 테스트를 하실 경우에 체크하세요."); ?>
                            <input type="radio" name="bc_kcp_is_test" value="0" <?php echo $billing_conf['bc_kcp_is_test'] == 0 ? "checked" : ""; ?> id="bc_kcp_is_test1">
                            <label for="bc_kcp_is_test1">실 결제</label>
                            <input type="radio" name="bc_kcp_is_test" value="1" <?php echo $billing_conf['bc_kcp_is_test'] == 1 ? "checked" : ""; ?> id="bc_kcp_is_test2">
                            <label for="bc_kcp_is_test2">테스트 결제</label>
                        </td>
                    </tr>

                    <tr class="pg_info_fld toss_info_fld" id="toss_info_anchor">
                        <th scope="row">
                            <label for="cf_lg_mid">토스페이먼츠 상점아이디</label><br>
                            <a href="http://sir.kr/main/service/lg_pg.php" target="_blank" class="toss_btn">토스페이먼츠 신청하기</a>
                        </th>
                        <td>
                            <?php echo help("토스페이먼츠 정기결제(빌링) 서비스는 개발 진행 중입니다. 빠른 시일 내 찾아뵙도록 노력하겠습니다. 감사합니다"); ?>
                        </td>
                    </tr>
                    <tr class="pg_info_fld toss_info_fld">
                        <th scope="row">결제 테스트</th>
                        <td>
                            <?php echo help("토스페이먼츠 결제 테스트를 하실 경우에 체크하세요."); ?>
                            <input type="radio" name="bc_toss_is_test" value="0" <?php echo $billing_conf['bc_toss_is_test'] == 0 ? "checked" : ""; ?> id="bc_toss_is_test1">
                            <label for="bc_toss_is_test1">실 결제</label>
                            <input type="radio" name="bc_toss_is_test" value="1" <?php echo $billing_conf['bc_toss_is_test'] == 1 ? "checked" : ""; ?> id="bc_toss_is_test2">
                            <label for="bc_toss_is_test2">테스트 결제</label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="btn_fixed_top">
        <a href=" <?php echo G5_SHOP_URL; ?>" class="btn btn_02">쇼핑몰</a>
        <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
    </div>

</form>

<script>
    function fconfig_check(f) {
        var msg = "",
            pg_msg = "";
        if (f.bc_pg_code.value == "kcp" && parseInt(f.bc_kcp_is_test.value) > 0) {
            pg_msg = "NHN KCP";
        } else if (f.bc_pg_code.value == "toss" && parseInt(f.bc_toss_is_test.value) > 0) {
            pg_msg = "토스페이먼츠";
        } else {
            return true;
        }

        msg += '(주의!) ' + pg_msg + ' 결제의 결제 설정이 현재 `테스트결제` 설정되어 있습니다.';
        msg += '\n쇼핑몰 운영중이면 반드시 실결제로 설정하여 운영하셔야 합니다.';
        msg += '\n실결제로 변경하려면 결제설정 탭 -> 결제 테스트에서 실결제를 선택해 주세요.';
        msg += '\n정말로 테스트결제로 설정하시겠습니까?';

        if (confirm(msg)) {
            return true;
        } else {
            return false;
        }
    }

    $(function() {
        $(".pg_info_fld").hide();
        // 초기설정 PG사 노출
        let default_pg = '<?php echo $billing_conf['bc_pg_code'] ?>';
        if (default_pg != '') {
            $("." + default_pg + "_info_fld").show();
        }

        // pg 선택
        $(document).on("click", ".de_pg_tab a", function(e) {
            var pg = $(this).attr("data-value"),
                class_name = "tab-current";

            $("#bc_pg_code").val(pg);
            $(this).parent("li").addClass(class_name).siblings().removeClass(class_name);

            $(".pg_info_fld:visible").hide();
            $("." + pg + "_info_fld").show();

        });
    });
</script>
<?php
require_once G5_ADMIN_PATH . '/admin.tail.php';
