<?php
$sub_menu = '100600';
require_once './_common.php';

$g5['title'] = '버전 업데이트';
require_once '../admin.head.php';

$page       = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;

$g5UpdateLog = new G5UpdateLog();
$logList    = $g5UpdateLog->getLogList($page);
$totalPage  = $g5UpdateLog->getPageCount();
?>
<h2 class="h2_frm">업데이트 로그 목록</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<form id="log_list_form" name="log_list_form" method="post" action="./log_delete.php" onsubmit="return log_list_form_submit(this);" autocomplete="off">
    <input type="hidden" name="action" value="list">
    <div class="btn_fixed_top">
        <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_01">
    </div>
    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
                <tr>
                    <th scope="col" style="width: 3%;">
                        <label for="chkall" class="sound_only">로그파일 전체</label>
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">파일명</th>
                    <th scope="col">상태</th>
                    <th scope="col">날짜</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($logList) && count($logList) > 0) { ?>
                    <?php foreach ($logList as $i => $var) { ?>
                        <tr>
                            <td class="td_chk">
                                <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($var['filename']); ?></label>
                                <input type="checkbox" name="chk[]" value="<?php echo get_text($var['filename']) ?>" id="chk_<?php echo $i; ?>">
                            </td>
                            <td><a href="./log_detail.php?filename=<?php echo $var['filename']; ?>"><?php echo $var['filename']; ?></a></td>
                            <td><a><?php echo $var['status']; ?></a></td>
                            <td><a><?php echo $var['datetime']; ?></a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4">로그파일 내역이 존재하지 않습니다.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
        if ($totalPage > 1) {
            echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $totalPage, $_SERVER['SCRIPT_NAME'] . '?page=');
        }
        ?>
    </div>
</form>
<script>
    function log_list_form_submit(f) {
        if (!is_checked("chk[]")) {
            alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
            return false;
        }

        if (document.pressed == "선택삭제") {
            if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
                return false;
            }
        }

        return true;
    }
</script>
<?php
require_once '../admin.tail.php';
