<?php
$sub_menu = '100600';
include_once('./_common.php');

$g5['title'] = '그누보드 업데이트';
include_once('../admin.head.php');

$current_version    = "v" . G5_GNUBOARD_VER; // api.github의 tag_name에는 버전번호 앞에 v가 붙어있음.
$version_list       = $g5['update']->getVersionList();
$latest_version     = $g5['update']->getLatestVersion();
$content            = $g5['update']->getVersionModifyContent($latest_version);
$connect_array      = array();

preg_match_all('/(?:(?:https?|ftp):)?\/\/[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i', $content, $match);
$content_url = $match[0];
foreach ($content_url as $key => $url) {
    $content = str_replace($url, "@" . $key . "@", $content);
}

if (function_exists("ftp_connect")) {
    $connect_array[] = 'ftp';
}
if (function_exists('ssh2_connect')) {
    $connect_array[] = 'sftp';
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

<?php if ($latest_version != false) { ?>
    <ul class="anchor">
        <li><a href="./">업데이트</a></li>
        <li><a href="./rollback.php">복원</a></li>
        <li><a href="./log.php">로그</a></li>
    </ul>
    <div class="version_box">
        <form method="POST" name="update_box" class="update_box" action="./step1.php" onsubmit="return update_submit(this);">
            <input type="hidden" name="compare_check" value="0">
            <?php if ($current_version != $latest_version) { ?>
                <table style="width:400px; text-align:left;">
                    <tbody>
                        <tr>
                            <th colspan="2">
                                <p>현재 그누보드 버전 : <?php echo $current_version; ?></p>
                            </th>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <p>최신 그누보드 버전 : <?php echo $latest_version; ?></p>
                            </th>
                        </tr>
                        <tr></tr>
                        <tr>
                            <th>목표버전</th>
                            <td>
                                <select class="target_version" name="target_version">
                                    <?php foreach ($version_list as $key => $version) { ?>
                                        <option value="<?php echo $version; ?>"><?php echo $version; ?></option>
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
                                <input id="username" name="username">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="password">사용자 비밀번호</label>
                            </th>
                            <td>
                                <input id="password" name="password">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <button type="button" class="btn btn_connect_check">ftp 연결확인</button>
                            </th>
                            <td class="update_btn_area">
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php } ?>
        </form>
        <div class="version_content_box" style="margin-top:30px;">
            <?php if (!empty($content)) {
                echo "<p class=\"content_title\">" . $latest_version . " 버전 수정</p>";
                echo "<p style=\"white-space:pre-line; line-height:2;\">";
                foreach ($content_url as $key => $url) {
                    $content = str_replace('@' . $key . '@', '<a class="a_style" href="' . $url . '" target="_blank">변경코드확인</a>', $content);
                }
                echo htmlspecialchars_decode($content, ENT_HTML5);
                echo "</p><br>";
            } ?>
        </div>
    </div>
<?php } else { ?>
    <div class="version_box">
        <p>정보 조회에 실패했습니다. 1시간 후 다시 시도해주세요.</p>
    </div>
<?php } ?>

<script>
    var inAjax = false;
    $(function() {

        $(".target_version").change(function() {
            $.ajax({
                url     : "./ajax.version_content.php",
                type    : "POST",
                dataType: "json",
                data    : {
                    'version': $(this).val(),
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
                    } else {
                        $(".version_content_box").empty();
                        $(".version_content_box").append(data['item']);
                    }
                },
                error: function(request, status, error) {
                    alert("code:" + request.status + "\n" + "message:" + request.responseText + "\n" + "error:" + error);
                },
                complete: function() {
                    inAjax = false;
                }
            });

            return false;
        })

        $(".btn_connect_check").click(function() {
            var version     = $(".target_version").val();
            var username    = $("#username").val();
            var password    = $("#password").val();
            var port        = $("input[name=\"port\"]:checked").val();

            $.ajax({
                url     : "./ajax.connect_check.php",
                type    : "POST",
                dataType: "json",
                data    : {
                    'username': username,
                    'password': password,
                    'port': port
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
                    alert(data.message);
                    if (data.error == 0) {
                        $(".update_btn_area").html("<button type=\"submit\" class=\"btn btn_update\">지금 업데이트</button>");
                    } else {
                        $(".update_btn_area").html("");
                    }
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

            return false;
        }
    }
</script>

<?php
include_once('../admin.tail.php');
