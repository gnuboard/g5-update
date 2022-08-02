<?php
$sub_menu = '100600';
require_once './_common.php';

$g5['title'] = '버전 업데이트';
require_once '../admin.head.php';

$rollbackFile   = isset($_POST['rollback_file']) ? clean_xss_tags($_POST['rollback_file'], 1, 1) : null;
$username       = isset($_POST['username']) ? clean_xss_tags($_POST['username'], 1, 1) : null;
$userPassword   = isset($_POST['password']) ? clean_xss_tags($_POST['password'], 1, 1) : null;
$port           = isset($_POST['port']) ? clean_xss_tags($_POST['port'], 1, 1) : null;

// 포트 연결
$g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $userPassword);
// 복구버전 조회
$rollback_version = $g5['update']->setRollbackVersion($rollbackFile);
// 목표버전 설정
$g5['update']->setTargetVersion($rollback_version);
// 롤백할 파일 목록 조회
$rollbackFileList = $g5['update']->getVersionCompareList();
?>
<p style="font-size:15px; font-weight:bold;">
    현재 서버 버전 : <?php echo $g5['update']->now_version; ?> -> 백업 파일 버전 : <?php echo $g5['update']->getTargetVersion(); ?> 복원 진행
</p>
<br>
<?php
$backupResult = $g5['update']->createBackupZipFile();
$updateResult['success']    = array();
$updateResult['fail']       = array();
$backupPath = "";
if ($backupResult == "success") {
    foreach ($rollbackFileList as $key => $var) {
        $exe = $g5['update']->os == "WINNT" ? "tar" : "zip";
        $backupPath = preg_replace('/.' . $exe . '/', '', G5_DATA_PATH . '/update/backup/' .  $rollbackFile);
        $originFilePath = G5_PATH . '/' . $var;
        $backupFilePath = $backupPath . '/' . $var;

        if (!file_exists($backupFilePath) && file_exists($originFilePath)) { // 백업파일은 존재하지않지만 서버파일은 존재할때
            $result = $g5['update']->deleteOriginFile($originFilePath);
            if ($result == "success") {
                $updateResult['success'][] = $var;
            } else {
                $updateResult['fail'][] = array('file' => $var, 'message' => $result);
            }
        }
        if (!is_dir(dirname($backupFilePath)) && is_dir(dirname($originFilePath))) { // 백업디렉토리는 존재하지않지만 서버디렉토리는 존재할때
            $result = $g5['update']->removeEmptyOriginDir(dirname($originFilePath));
            if ($result == "success") {
                $updateResult['success'][] = $var;
            } else {
                $updateResult['fail'][] = array('file' => $var, 'message' => $result);
            }
        }
        $result = $g5['update']->writeUpdateFile($originFilePath, $backupFilePath);
        if ($result == "success") {
            $updateResult['success'][] = $var;
        } else {
            $updateResult['fail'][] = array('file' => $var, 'message' => $result);
        }
    }
    $g5['update']->deleteBackupDir($backupPath);

    
    /**
     * 데이터베이스 업데이트
     * @todo 테스트 필요
     */
    if ($is_db_update) {
        $migration = new G5Migration();
        $migration->update($rollback_version);
    }
} else {
    $updateResult['fail'][] = array('file' => "", 'message' => $backupResult);
}

$g5UpdateLog  = new G5UpdateLog();
$g5UpdateLog->createLogFile($updateResult, 'update');

$g5['update']->disconnect();

?>
<div>
    <p style="font-weight:bold; font-size:15px;">복원 성공</p>
    <?php if (isset($updateResult['success'])) { ?>
        <?php foreach ($updateResult['success'] as $key => $var) { ?>
            <p><?php echo $var; ?></p>
        <?php } ?>
    <?php } ?>
    <br>

    <p style="font-weight:bold; font-size:15px;">백업본에 존재하지 않아 제거된 파일</p>
    <?php if (isset($updateResult['fail'])) { ?>
        <?php foreach ($updateResult['fail'] as $key => $var) { ?>
            <p><span style="color:red;"><?php echo $var['file']; ?></span><?php echo ' : ' . $var['message']; ?></p>
        <?php } ?>
    <?php } ?>
</div>
<?php
require_once '../admin.tail.php';
