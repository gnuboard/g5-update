<?php
$sub_menu = '100600';
require_once './_common.php';

$g5['title'] = '버전 업데이트';
require_once '../admin.head.php';

$filename = isset($_REQUEST['filename']) ? clean_xss_tags($_REQUEST['filename']) : null;

$g5UpdateLog = new G5UpdateLog();
$logDetail = $g5UpdateLog->getLogDetail($filename);
?>
<h2 class="h2_frm">업데이트 로그 상세정보</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<form id="log_form" name="log_list_form" method="post" action="./log_delete.php" onsubmit="return log_form_submit(this);" autocomplete="off">
    <input type="hidden" name="action" value="form">
    <input type="hidden" name="filename" value="<?php echo $logDetail['filename']; ?>">
    <div class="btn_fixed_top">
        <input type="submit" name="act_button" value="삭제" onclick="document.pressed=this.value" class="btn btn_01">
    </div>
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
                    <td><?php echo $logDetail['filename']; ?></td>
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
</form>
<script>
    function log_form_submit(f) {
        if (document.pressed == "삭제") {
            if (!confirm("정말 삭제하시겠습니까?")) {
                return false;
            }
        }

        return true;
    }
</script>
<?php
include_once '../admin.tail.php';
