<?php
$sub_menu = '100600';
include_once('./_common.php');

$g5['title'] = '버전 업데이트';
include_once('../admin.head.php');

$current_version    = "v" . G5_GNUBOARD_VER; // api.github의 tag_name에는 버전번호 앞에 v가 붙어있음.
$version_list       = $g5['update']->getVersionList();
$latest_version     = $g5['update']->getLatestVersion();
$content            = $g5['update']->getVersionModifyContent($latest_version);
$connect_array      = $g5['update']->getProtocolList();

preg_match_all('/(?:(?:https?|ftp):)?\/\/[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i', $content, $match);
$content_url = $match[0];
foreach ($content_url as $key => $url) {
    $content = str_replace($url, "@" . $key . "@", $content);
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
    <h2 class="h2_frm">버전 업데이트 설정</h2>
    <ul class="anchor">
        <li><a href="./">업데이트</a></li>
        <li><a href="./rollback.php">복원</a></li>
        <li><a href="./log.php">로그</a></li>
    </ul>
    <?php if ($latest_version != false) { ?>
    <form method="POST" name="update_box" class="update_box" action="./step1.php" onsubmit="return update_submit(this);">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>버전 업데이트 설정</caption>
                <colgroup>
                    <col class="grid_4">
                    <col class="grid_8">
                    <col class="grid_18">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="current_version">현재 그누보드 버전</label></th>
                        <td><h3><?php echo $current_version; ?></h3></td>
                        <th class="version_title_box">
                            <?php echo (!empty($content)) ? "<p>" . $latest_version . " 버전 수정</p>" : ""; ?>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latest_version">최신 그누보드 버전</label></th>
                        <td><h3><?php echo $latest_version; ?></h3></td>
                        <td rowspan="5" style="padding:0px;">
                            <div style="width:100%; height:300px; overflow:auto;">
                                <table>
                                    <tr>
                                        <td class="version_content_box">
                                        <?php if (!empty($content)) {
                                            echo "<p>";
                                            foreach ($content_url as $key => $url) {
                                                $content = str_replace('@' . $key . '@', '<a class="a_style" href="' . $url . '" target="_blank">변경코드확인</a>', $content);
                                            }
                                            echo $g5['update']->setHtmlspecialcharsDecode($content);
                                            echo "</p><br>";
                                        } ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="target_version">업데이트 버전 선택</label></th>
                        <td>
                            <select class="target_version" name="target_version">
                                <?php foreach ($version_list as $key => $version) { ?>
                                    <option value="<?php echo $version; ?>"><?php echo $version; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latest_version">포트</label></th>
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
                        <th scope="row"><label for="latest_version">사용자 이름</label></th>
                        <td><input id="username" name="username" class="frm_input"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latest_version">사용자 비밀번호</label></th>
                        <td>
                            <input type="password" id="password" name="password" class="frm_input">
                            <button type="button" class="btn_connect_check btn_frmline">연결확인</button>
                            <span class="update_btn_area"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
    <?php } else { ?>
    <div class="version_box">
        <p>정보 조회에 실패했습니다. 1시간 후 다시 시도해주세요.</p>
    </div>
    <?php } ?>
</section>

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
                        $(".version_title_box").html(data['title']);
                        $(".version_content_box").html(data['item']);
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
                        $(".update_btn_area").html("<button type=\"submit\" class=\"btn btn_submit\" style=\"height:35px\">지금 업데이트</button>");
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
