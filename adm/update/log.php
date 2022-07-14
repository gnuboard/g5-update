<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
$logList = $g5['update']->getLogList($page);
$totalPage = $g5['update']->getLogListSize();
?>
<h2 class="h2_frm">업데이트 로그 목록</h2>
<ul class="anchor">
    <li><a href="./">업데이트</a></li>
    <li><a href="./rollback.php">복원</a></li>
    <li><a href="./log.php">로그</a></li>
</ul>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">파일명</th>
            <th scope="col">상태</th>
            <th scope="col">날짜</th>
        </tr>
        </thead>
        <tbody>
        <?php if (is_array($logList) && count($logList) > 0) { ?>
            <?php foreach ($logList as $key => $var) { ?>
            <tr>
                <td><a href="./log_detail.php?filename=<?php echo $var['filename']; ?>"><?php echo $var['filename']; ?></a></td>
                <td><a><?php echo $var['status']; ?></a></td>
                <td><a><?php echo $var['datetime']; ?></a></td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="3">로그파일 내역이 존재하지 않습니다.</td></tr>
        <?php } ?>
        </tbody>
    </table>
    <?php
    if ($totalPage > 1) {
        echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $totalPage, $_SERVER['SCRIPT_NAME'] . '?' . $qstr . '&amp;page=');
    }
    ?>
</div>
<?php
include_once '../admin.tail.php';
