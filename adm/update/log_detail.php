<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$filename = isset($_REQUEST['filename']) ? $_REQUEST['filename'] : null;
if ($filename == null) {
    alert("파일이름이 존재하지 않습니다.");
}

$log_dir = G5_DATA_PATH . "/update/log";
if (!is_dir($log_dir)) {
    alert("로그 디렉토리가 존재하지 않습니다.");
}

$log_detail = $g5['update']->getLogDetail($filename);
if ($log_detail == false) {
    alert('파일의 내용을 읽어올 수 없습니다.');
}
?>

<h2 class="h2_frm">업데이트 로그 상세정보</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>업데이트 로그 상세정보</caption>
        <colgroup>
            <col class="grid_4">
            <col class="grid_8">
            <col class="grid_4">
            <col class="grid_8">
        </colgroup>
        <tbody>
            <tr>
                <th>파일명</th>
                <td><?php echo $filename; ?></td>
                <th>생성날짜</th>
                <td><?php echo $log_detail['datetime']; ?></td>
            </tr>
            <tr>
                <th>타입</th>
                <td colspan="3"><?php echo $log_detail['status']; ?></td>
            </tr>
            <tr>
                <th>내용</th>
                <td colspan="3"><?php echo nl2br($log_detail['content']); ?></td>
            </tr>
        </tbody>
    </table>
</div>
<?php
include_once '../admin.tail.php';
