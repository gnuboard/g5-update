<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$filename = isset($_REQUEST['filename']) ? clean_xss_tags($_REQUEST['filename']) : null;
$logDetail = $g5['update']->getLogDetail($filename);
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
                <td><?php echo $logDetail['datetime']; ?></td>
            </tr>
            <tr>
                <th>타입</th>
                <td colspan="3"><?php echo $logDetail['status']; ?></td>
            </tr>
            <tr>
                <th>내용</th>
                <td colspan="3"><?php echo nl2br($logDetail['content']); ?></td>
            </tr>
        </tbody>
    </table>
</div>
<?php
include_once '../admin.tail.php';
