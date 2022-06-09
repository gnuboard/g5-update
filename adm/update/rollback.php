<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$current_version    = "v" . G5_GNUBOARD_VER; // api.github의 tag_name에는 버전번호 앞에 v가 붙어있음.
$backup_list        = $g5['update']->getBackupList(G5_DATA_PATH . "/update/backup");
$latest_version     = $g5['update']->getLatestVersion();
$content            = $g5['update']->getVersionModifyContent($latest_version);
$connect_array      = $g5['update']->getProtocolList();

preg_match_all('/(?:(?:https?|ftp):)?\/\/[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i', $content, $match);
$content_url = $match[0];
foreach ($content_url as $key => $var) {
    $content = str_replace($var, "@" . $key . "@", $content);
}
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

    .content_title {
        font-size: 16px;
        font-weight: bold;
    }
</style>
<section>
    <h2 class="h2_frm">업데이트 복원</h2>
    <ul class="anchor">
        <li><a href="./">업데이트</a></li>
        <li><a href="./rollback.php">복원</a></li>
        <li><a href="./log.php">로그</a></li>
    </ul>
    <?php if ($backup_list != false) { ?>
    <form method="POST" name="update_box" class="update_box" action="./rollback_step1.php" onsubmit="return update_submit(this);">
        <input type="hidden" name="compare_check" value="0">
        <div class="tbl_frm01 tbl_wrap">
            <?php if ($current_version != $latest_version) { ?>
                <table style="width:650px; text-align:left;">
                    <tbody>
                        <tr>
                            <th>현재 그누보드 버전</th>
                            <td><h3><?php echo $current_version; ?></h3></td>
                        </tr>
                        <tr>
                            <th>복원시점</th>
                            <td>
                                <select class="target_version" name="rollback_file">
                                    <?php foreach ($backup_list as $key => $var) { ?>
                                        <option value="<?php echo $var['realName']; ?>"><?php echo $var['listName']; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>포트</th>
                            <td>
                            <?php if (!empty($connect_array)) { ?>
                                <?php foreach ($connect_array as $key => $connect) { ?>
                                    <label for="<?php echo $connect; ?>"><?php echo $connect; ?></label>
                                    <input id="<?php echo $connect; ?>" type="radio" name="port" value="<?php echo $connect; ?>" <?php echo $key == 0 ? "checked" : "" ?>>
                                <?php } ?>
                            <?php } else { ?>
                                <p>통신연결 lib가 존재하지 않습니다.</p>
                            <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="username">사용자 이름</label>
                            </th>
                            <td>
                                <input id="username" name="username" class="frm_input">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="password">사용자 비밀번호</label>
                            </th>
                            <td>
                                <input id="password" name="password" class="frm_input">
                                <button type="button" class="btn_connect_check btn_frmline">FTP 연결확인</button>
                                <span class="update_btn_area"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            <?php } ?>
        </div>
    </form>
    <?php } else { ?>
    <div class="version_box">
        <?php if ($latest_version == false) { ?>
        <p>정보조회에 실패했습니다.</p>
        <?php } else { ?>
        <p>복원시점이 존재하지 않습니다. 업데이트를 진행하고 접근해주세요.</p>
        <?php } ?>
    </div>
    <?php } ?>
</section>

<script>
    var inAjax = false;
    $(function() {
        $(".btn_connect_check").click(function() {
            var username = $("#username").val();
            var password = $("#password").val();
            var port = $("input[name=\"port\"]:checked").val();

            $.ajax({
                url: "./ajax.connect_check.php",
                type: "POST",
                data: {
                    'username': username,
                    'password': password,
                    'port': port
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    if (inAjax == false) {
                        inAjax = true;
                    } else {
                        alert("현재 통신중입니다.");
                        return false;
                    }
                },
                success: function(data) {
                    alert(data.message);
                    if (data.error != 0) {
                        return false;
                    }

                    $(".update_btn_area").html("<button type=\"submit\" class=\"btn btn_submit\" style=\"height:35px\">선택한 시점으로 롤백</button>");
                },
                error: function(request, status, error) {
                    alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
                },
                complete: function() {
                    inAjax = false;
                }
            });

            return false;
        });
    })

    function update_submit(f) {
        var admin_password = prompt("관리자 비밀번호를 입력해주세요");
        if (admin_password == "") {
            alert("관리자 비밀번호없이 접근이 불가능합니다.");
            return false;
        } else {
            $.ajax({
                type: 'POST',
                url: './ajax.password_check.php',
                dataType: 'json',
                data: {
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
                    inAjax = false;
                    if (data.error != 0) {
                        alert(data.message);
                        return false;
                    }

                    f.submit();
                },
                error: function(request, status, error) {
                    inAjax = false;
                    alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
                },
                complete: function() {
                    inAjax = false;
                }
            });

            return false;
        }
    }
</script>

<?php
include_once '../admin.tail.php';
