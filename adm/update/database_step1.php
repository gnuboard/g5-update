<?php
$sub_menu = '100700';
include_once './_common.php';

$g5['title'] = '데이터베이스 업데이트';
include_once '../admin.head.php';

$migration = new G5Migration();
$migration->update();
?>
<section>
    <h2 class="h2_frm">데이터베이스 업데이트 설정</h2>
    <form method="POST" name="update_box" class="update_box" action="" onsubmit="return update_submit(this);">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>데이터베이스 업데이트 설정</caption>
                <colgroup>
                    <col class="grid_4">
                    <col class="grid_8">
                    <col class="grid_18">
                </colgroup>
                <tbody>
                    <tr>
                    </tr>
                    <tr>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
</section>
<?php
include_once '../admin.tail.php';
