<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$rollback_file  = isset($_POST['rollback_file']) ? $_POST['rollback_file'] : null;
$username       = isset($_POST['username']) ? $_POST['username'] : null;
$userpassword   = isset($_POST['password']) ? $_POST['password'] : null;
$port           = isset($_POST['port']) ? $_POST['port'] : null;

$totalSize      = $g5['update']->getTotalStorageSize();
$freeSize       = $g5['update']->getUseableStorageSize();
$useSize        = $g5['update']->getUseStorageSize();
$usePercent     = $g5['update']->getUseStoragePercenty();

if ($g5['update']->checkInstallAvailable() == false) {
    die("가용용량이 부족합니다. (20MB 이상)");
}
if ($rollback_file == null) {
    die("롤백할 파일이 선택되지 않았습니다.");
}
if ($port == null) {
    die("포트가 입력되지 않았습니다.");
}
if ($username == null) {
    die("{$port} 계정명이 입력되지 않았습니다.");
}
if ($userpassword == null) {
    die("{$port} 비밀번호가 입력되지 않았습니다.");
}

$conn_result = $g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $userpassword);
if ($conn_result == false) {
    die("연결에 실패했습니다.");
}

$result = $g5['update']->unzipBackupFile($rollback_file);
if ($result == false) {
    die("압축해제에 실패했습니다.");
}

$rollback_version = $g5['update']->setRollbackVersion($rollback_file);
$g5['update']->setTargetVersion($rollback_version);
$compare_list = $g5['update']->getVersionCompareList();
if ($compare_list == null) {
    die("비교파일리스트가 존재하지 않습니다.");
}

$compare_check = $g5['update']->checkRollbackVersionComparison($compare_list, $rollback_file);
if ($compare_check == false) {
    die("파일 비교에 실패했습니다.");
}

$plist = $g5['update']->getDepthVersionCompareList($compare_list, $compare_check);
?>

<h2 class="h2_frm">업데이트 복원 진행</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<form method="POST" name="update_box" class="update_box" action="./rollback_step2.php" onsubmit="return update_submit(this);">
    <input type="hidden" name="compare_check" value="<?php echo $compare_check['type']; ?>">
    <input type="hidden" name="username" value="<?php echo get_text($username); ?>">
    <input type="hidden" name="password" value="<?php echo get_text($userpassword); ?>">
    <input type="hidden" name="port" value="<?php echo get_text($port); ?>">
    <input type="hidden" name="rollback_file" value="<?php echo get_text($rollback_file); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>업데이트 복원 진행</caption>
            <colgroup>
                <col class="grid_4">
                <col class="grid_8">
                <col class="grid_18">
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row">버전</th>
                    <td><?php echo $g5['update']->now_version . " ▶ "?><span style="font-weight:bold;"><?php echo $rollback_version ?></span></td>
                    <th>파일 목록</th>
                </tr>
                <tr>
                    <th scope="row">사용량 / 전체 용량</th>
                    <td><?php echo $useSize . " / " . $totalSize . " (" . $usePercent . "%)"; ?></td>
                    <td rowspan="2" style="padding:0px;">
                        <div style="width:100%; height:300px; overflow:auto;">
                            <table>
                                <tr>
                                    <td style="line-height:2; border: none !important;">
                                    <?php print_r($g5['update']->changeDepthListPrinting($plist)); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="vertical-align: top;">
                    <?php if ($compare_check['type'] == 'Y') { ?>
                        <button type="submit" class="btn btn_submit">업데이트 진행</button>
                    <?php } else { ?>
                        <p style="color:red; font-weight:bold;">롤백이 진행될 파일리스트 입니다.</p>
                        <div style="margin-top:30px;">
                            <button type="submit" class="btn btn_submit">복원 진행</button>
                            <button type="button" class="btn btn_03 btn_cancel">복원 진행 취소</button>
                        </div>
                    <?php } ?>
                </tr>
            </tbody>
        </table>
    </div>
</form>
<script>
    $(".btn_cancel").click(function() {
        history.back();
    })

    function update_submit(f) {
        if (f.compare_check.value == 'N') {
            if (confirm("기존에 변경한 파일에 문제가 발생할 수 있습니다.\n복원을 진행하시겠습니까?")) {
                return true;
            }

            return false;
        }

        return true;
    }
</script>

<?php
include_once '../admin.tail.php';
