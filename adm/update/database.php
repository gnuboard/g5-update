<?php
$sub_menu = '100700';
include_once './_common.php';

$g5['title'] = '데이터베이스 업데이트';
include_once '../admin.head.php';

/**
 * @todo 현재 데이터베이스 버전이 최신버전인지 표시 추가
 */
$g5Migration = new G5Migration();
$migrationVersion = $g5Migration->getMigrationVersion();
?>
<section>
    <h2 class="h2_frm">데이터베이스 업데이트 설정</h2>
    <form method="POST" name="update_box" class="update_box" action="./database_step1.php" onsubmit="return update_submit(this);">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>데이터베이스 업데이트 설정</caption>
                <colgroup>
                    <col class="grid_4">
                    <col class="grid_18">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="latest_version">현재 데이터베이스 버전</label></th>
                        <td>
                            <?php echo $migrationVersion; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latest_version">수동 업데이트 진행</label></th>
                        <td>
                            <span class="frm_info">그누보드 버전 업데이트 시 데이터베이스 업데이트를 진행하지 않았다면 최신버전 업데이트를 권장합니다.</span>
                            <button type="submit" class="btn_connect_check btn_frmline">업데이트</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
</section>
<script>
function update_submit(f) {
    var inAjax = false;
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
                if (inAjax) {
                    alert("현재 통신중입니다.");
                    return false;
                } else {
                    inAjax = true;
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
