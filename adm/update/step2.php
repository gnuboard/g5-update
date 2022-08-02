<?php
$sub_menu = '100600';
require_once './_common.php';

$g5['title'] = '버전 업데이트';
require_once '../admin.head.php';

$targetVersion  = isset($_POST['target_version']) ? clean_xss_tags($_POST['target_version'], 1, 1) : null;
$username       = isset($_POST['username']) ? clean_xss_tags($_POST['username'], 1, 1) : null;
$userPassword   = isset($_POST['password']) ? clean_xss_tags($_POST['password'], 1, 1) : null;
$port           = isset($_POST['port']) ? clean_xss_tags($_POST['port'], 1, 1) : null;
$is_db_update   = isset($_POST['is_db_update']) ? clean_xss_tags($_POST['is_db_update'], 1, 1) : 0;

// 포트 연결
$g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $userPassword);
// 목표버전 설정
$g5['update']->setTargetVersion($targetVersion);
// 비교파일 목록 조회
$compare_list = $g5['update']->getVersionCompareList();
// 업데이트 버전 다운로드
$g5['update']->downloadVersion($targetVersion);
?>
<h2 class="h2_frm">버전 업데이트 진행</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<p style="font-size:15px; font-weight:bold;"><?php echo $g5['update']->getTargetVersion(); ?> 버전 파일 다운로드 완료</p>
<br>
<?php
$backupResult = $g5['update']->createBackupZipFile();
$updateResult['success']    = array();
$updateResult['fail']       = array();
if ($backupResult == "success") {
    foreach ($compare_list as $key => $var) {

        $originFilePath = G5_PATH . '/' . $var;
        $changeFilePath = G5_DATA_PATH . '/update/version/' . $targetVersion . '/' . $var;

        if (!file_exists($changeFilePath) && file_exists($originFilePath)) { // 업데이트파일은 존재하지않지만 현재파일은 존재할때
            $result = $g5['update']->deleteOriginFile($originFilePath);
            if ($result == "success") {
                $updateResult['success'][] = $var;
            } else {
                $updateResult['fail'][] = array('file' => $var, 'message' => $result);
            }
        }
        if (!is_dir(dirname($changeFilePath)) && is_dir(dirname($originFilePath))) { // 업데이트디렉토리는 존재하지않지만 현재디렉토리는 존재할때
            $result = $g5['update']->removeEmptyOriginDir(dirname($originFilePath));
            if ($result == "success") {
                $updateResult['success'][] = $var;
            } else {
                $updateResult['fail'][] = array('file' => $var, 'message' => $result);
            }
        }

        $result = $g5['update']->writeUpdateFile($originFilePath, $changeFilePath);
        if ($result == "success") {
            $updateResult['success'][] = $var;
        } else {
            $updateResult['fail'][] = array('file' => $var, 'message' => $result);
        }
    }
    // 데이터베이스 업데이트
    if ($is_db_update) {
        $migration = new G5Migration();
        $migration->update($targetVersion);
    }
} else {
    $updateResult['fail'][] = array('file' => "", 'message' => $backupResult);
}

// 실행로그 기록
$g5UpdateLog  = new G5UpdateLog();
$g5UpdateLog->createLogFile($updateResult, 'update');

$g5['update']->disconnect();
?>
<div>
    <p style="font-weight:bold; font-size:15px;">업데이트 성공</p>
    <?php if (isset($updateResult['success'])) { ?>
        <?php foreach ($updateResult['success'] as $key => $var) { ?>
            <p><?php echo $var; ?></p>
        <?php } ?>
    <?php } ?>
    <br>

    <p style="font-weight:bold; font-size:15px;">업데이트 실패</p>
    <?php if (isset($updateResult['fail'])) { ?>
        <?php foreach ($updateResult['fail'] as $key => $var) { ?>
            <p><span style="color:red;"><?php echo $var['file']; ?></span><?php echo ' : ' . $var['message']; ?></p>
        <?php } ?>
    <?php } ?>
</div>
<?php
require_once '../admin.tail.php';
