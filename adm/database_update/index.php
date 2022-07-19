<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$migration = new G5Migration();

?>
<h2 class="h2_frm">DB 업그레이드 테스트 페이지</h2>

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
            <tr><td colspan="3">테스트 페이지</td></tr>
        </tbody>
    </table>
</div>
<?php
include_once '../admin.tail.php';
