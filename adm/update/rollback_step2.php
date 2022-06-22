<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '그누보드 step2';
include_once '../admin.head.php';

$rollback_file  = isset($_POST['rollback_file']) ? $_POST['rollback_file'] : null;
$username       = isset($_POST['username']) ? $_POST['username'] : null;
$userpassword   = isset($_POST['password']) ? $_POST['password'] : null;
$port           = isset($_POST['port']) ? $_POST['port'] : null;

$conn_result = $g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $password);
if ($conn_result == false) {
    die("연결에 실패했습니다.");
}

$rollback_version = $g5['update']->setRollbackVersion($rollback_file);
$g5['update']->setTargetVersion($rollback_version);
$list = $g5['update']->getVersionCompareList();
if ($list == null) {
    die("비교파일리스트가 존재하지 않습니다.");
}

?>
<p style="font-size:15px; font-weight:bold;">현재 서버 버전 : <?php echo $g5['update']->now_version; ?> -> 백업 파일 버전 : <?php echo $g5['update']->target_version; ?> 복원 진행</p>
<br>
<?php
$result = $g5['update']->createBackupZipFile();
if ($result == "success") {
    $update_check = array();
    foreach ($list as $key => $var) {
        $exe = $g5['update']->os == "WINNT" ? "tar" : "zip";
        $backupPath = preg_replace('/.' . $exe . '/', '', G5_DATA_PATH . '/update/backup/' .  $rollback_file);
        $originFilePath = G5_PATH . '/' . $var;
        $backupFilePath = $backupPath . '/' . $var;

        if (!file_exists($backupFilePath) && file_exists($originFilePath)) { // 백업파일은 존재하지않지만 서버파일은 존재할때
            $result = $g5['update']->deleteOriginFile($originFilePath);
            if ($result == "success") {
                $update_check['success'][] = $var;
            } else {
                $update_check['fail'][] = array('file' => $var, 'message' => $result);
            }
        }
        if (!is_dir(dirname($backupFilePath)) && is_dir(dirname($originFilePath))) { // 백업디렉토리는 존재하지않지만 서버디렉토리는 존재할때
            $result = $g5['update']->removeEmptyOriginDir(dirname($originFilePath));
            if ($result == "success") {
                $update_check['success'][] = $var;
            } else {
                $update_check['fail'][] = array('file' => $var, 'message' => $result);
            }
        }
        $result = $g5['update']->writeUpdateFile($originFilePath, $backupFilePath);
        if ($result == "success") {
            $update_check['success'][] = $var;
        } else {
            $update_check['fail'][] = array('file' => $var, 'message' => $result);
        }
    }
    $g5['update']->deleteBackupDir($backupPath);
} else {
    $update_check['fail'][] = array('file' => "", 'message' => $result);
}

$result = $g5['update']->writeLogFile($update_check['success'], $update_check['fail'], 'rollback');

$g5['update']->disconnect();

?>
<div>
    <p style="font-weight:bold; font-size:15px;">복원 성공</p>
    <?php if (is_array($update_check['success'])) { ?>
        <?php foreach ($update_check['success'] as $key => $var) { ?>
            <p><?php echo $var; ?></p>
        <?php } ?>
    <?php } ?>
    <br>

    <p style="font-weight:bold; font-size:15px;">백업본에 존재하지 않아 제거된 파일</p>
    <?php if (is_array($update_check['fail'])) { ?>
        <?php foreach ($update_check['fail'] as $key => $var) { ?>
            <p><span style="color:red;"><?php echo $var['file']; ?></span><?php echo ' : ' . $var['message']; ?></p>
        <?php } ?>
    <?php } ?>
</div>

<?php
include_once '../admin.tail.php';
?>