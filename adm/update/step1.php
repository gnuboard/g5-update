<?php
$sub_menu = '100600';
include_once './_common.php';

$g5['title'] = '버전 업데이트';
include_once '../admin.head.php';

$targetVersion  = isset($_POST['target_version']) ? clean_xss_tags($_POST['target_version'], 1, 1) : null;
$username       = isset($_POST['username']) ? clean_xss_tags($_POST['username'], 1, 1) : null;
$userPassword   = isset($_POST['password']) ? clean_xss_tags($_POST['password'], 1, 1) : null;
$port           = isset($_POST['port']) ? clean_xss_tags($_POST['port'], 1, 1) : null;

$totalSize      = $g5['update']->getTotalStorageSize();
$freeSize       = $g5['update']->getUseableStorageSize();
$useSize        = $g5['update']->getUseStorageSize();
$usePercent     = $g5['update']->getUseStoragePercenty();

// 포트 연결
$g5['update']->connect($_SERVER['HTTP_HOST'], $port, $username, $userPassword);
// 설치가능한 디스크 공간 체크
$g5['update']->checkInstallAvailable();
// update 디렉토리 생성
$g5['update']->makeUpdateDir();
// 목표버전 설정
$g5['update']->setTargetVersion($targetVersion);
// 업데이트할 파일 목록 조회
$updateFileList = $g5['update']->getVersionCompareList();
// 업데이트 파일과 현재 그누보드 파일 비교
$checkCustomFile = $g5['update']->checkSameVersionComparison($updateFileList);
// 업데이트 파일목록 데이터 처리
$updateList = $g5['update']->getDepthVersionCompareList($updateFileList, $checkCustomFile);
// html 태그 추가
$viewUpdateList = $g5['update']->changeDepthListPrinting($updateList);
?>
<section>
    <h2 class="h2_frm">버전 업데이트 진행</h2>
    <ul class="anchor">
        <li><a href="./">업데이트</a></li>
        <li><a href="./rollback.php">복원</a></li>
        <li><a href="./log.php">로그</a></li>
    </ul>

    <form method="POST" name="update_box" class="update_box" action="./step2.php" onsubmit="return update_submit(this);">
        <input type="hidden" name="compare_check" value="<?php echo $checkCustomFile['type']; ?>">
        <input type="hidden" name="username" value="<?php echo get_text($username); ?>">
        <input type="hidden" name="password" value="<?php echo get_text($userPassword); ?>">
        <input type="hidden" name="port" value="<?php echo get_text($port); ?>">
        <input type="hidden" name="target_version" value="<?php echo get_text($targetVersion); ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>버전 업데이트 진행</caption>
                <colgroup>
                    <col class="grid_4">
                    <col class="grid_8">
                    <col class="grid_18">
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row">업데이트 버전</th>
                        <td><h3><?php echo $targetVersion ?></h3></td>
                        <th>업데이트 파일 목록</th>
                    </tr>
                    <tr>
                        <th scope="row">사용량 / 전체 용량</th>
                        <td><?php echo $useSize . " / " . $totalSize . " (" . $usePercent . "%)"; ?></td>
                        <td rowspan="2" style="padding:0px;">
                            <div style="width:100%; height:300px; overflow:auto;">
                                <table>
                                    <tr>
                                        <td style="line-height:2; border: none !important;">
                                        <?php print_r($viewUpdateList); ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="vertical-align: top;">
                        <?php if ($checkCustomFile['type'] == 'Y') { ?>
                            <button type="submit" class="btn btn_submit">업데이트 진행</button>
                        <?php } else { ?>
                            <p style="color:red; font-weight:bold;">기존 버전간의 변경된 파일이 존재합니다.</p>
                            <button type="submit" class="btn btn_submit">강제 업데이트 진행</button>
                        <?php } ?>
                            <button type="button" class="btn btn_03 btn_cancel">업데이트 진행 취소</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
</section>

<script>
    $(".btn_cancel").click(function() {
        history.back();
    })

    function update_submit(f) {
        if (f.compare_check.value == 'N') {
            if (confirm("기존에 변경한 파일에 문제가 발생할 수 있습니다.\n업데이트를 진행하시겠습니까?")) {
                return true;
            }
            return false;
        }
        return true;
    }
</script>

<?php
include_once '../admin.tail.php';
