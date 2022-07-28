<?php
$sub_menu = '100700';
include_once './_common.php';

$g5['title'] = '데이터베이스 업데이트';
include_once '../admin.head.php';
?>
<style>
    .a_style {
        font-weight: 400;
        padding: 0.2em 0.4em;
        margin: 0;
        font-size: 12px;
        background-color: #ddf4ff;
        border-radius: 6px;
        border: 1px;
        color: #0969da;
    }

    .version_title_box p {
        font-size: 16px;
        font-weight: bold;
    }
    .version_content_box {
        border: none !important;
    }
    .version_content_box p {
        white-space:pre-line; line-height:2;
    }
</style>

<section>
    <h2 class="h2_frm">데이터베이스 업데이트 설정</h2>
    <form method="POST" name="update_box" class="update_box" action="" onsubmit="return update_submit(this);">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>데이터베이스 업데이트 설정</caption>
                <colgroup>
                    <col class="grid_4">
                    <col class="grid_8">
                    <col class="grid_18">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="current_version">자동 업데이트 설정</label></th>
                        <td>
                            <span class="frm_info">버전 업데이트 시 자동으로 DB 업데이트가 진행되도록 설정합니다.</span>
                            <input type="checkbox" name="cf_use_copy_log" value="1" id="cf_use_copy_log" checked=""> 사용
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latest_version">수동 업데이트 진행</label></th>
                        <td>
                            <button type="submit" class="btn_connect_check btn_frmline">업데이트</button>
                            <span class="update_btn_area"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
</section>
<script>
function update_submit(f) {

    var admin_password = prompt("관리자 비밀번호를 입력해주세요");

    if (admin_password == "") {
        alert("관리자 비밀번호없이 접근이 불가능합니다.");
        return false;
    } else {
        $.ajax({
            type    : 'POST',
            url     : './ajax.password_check.php',
            dataType: 'json',
            data    : {
                'admin_password': admin_password
            },
            beforeSend: function(xhr) {
                if (inAjax == false) {
                    inAjax = true;
                } else {
                    alert("현재 통신중입니다.");
                    return false;
                }
            },
            success: function(data) {
                if (data.error != 0) {
                    alert(data.message);
                    return false;
                }
                f.submit();
            },
            error: function(request, status, error) {
                alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
            },
            complete: function() {
                inAjax = false;
            }
        });
    }

    return false;
}
</script>
<?php
include_once '../admin.tail.php';
