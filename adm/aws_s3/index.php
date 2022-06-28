<?php

$sub_menu = '100920'; //메뉴는 admin.head 이전에 선언
include_once(dirname(__FILE__) . '/_common.php');
include_once(G5_ADMIN_PATH . '/admin.head.php');
include_once(G5_ADMIN_PATH . '/admin.lib.php');

if (version_compare(PHP_VERSION, '5.5', '<')) {
    echo help('AWS S3 저장소는 PHP 5.5버전 이상만 이용하실 수있습니다.');
    include_once(G5_ADMIN_PATH . '/admin.tail.php');
    exit;
}
auth_check_menu($auth, $sub_menu, 'r');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/aws_s3/adm.style.css">', 0);

include_once(G5_LIB_PATH . '/AwsSdk/aws-autoloader.php');
include_once(G5_PLUGIN_PATH . '/AwsS3/S3Service.php');
$admin_aws_config = array(
    'bucket_name' => '',
    'access_key' => '',
    'bucket_region' => '',
    'is_save_host' => '',
    'is_only_use_s3' => ''
);

if (file_exists(G5_DATA_PATH . '/s3config.php')) {
    $admin_aws_config['bucket_name'] = G5_S3_BUCKET_NAME;
    $admin_aws_config['bucket_region'] = G5_S3_REGION;
}

$table_name = G5_TABLE_PREFIX . \Gnuboard\Plugin\AwsS3\S3Service::get_instance()->get_table_name();
$sql = "select * from $table_name";

if ($row = sql_fetch($sql, false)) {
    $admin_aws_config['is_only_use_s3'] = $row['is_only_use_s3'];
    $admin_aws_config['access_control_list'] = $row['acl_default_value'];
}

$all_regions = \Gnuboard\Plugin\AwsS3\S3Service::get_instance()->get_regions();
$status = \Gnuboard\Plugin\AwsS3\S3Service::get_instance()->get_connect_status();

echo $status['message'];
if (!empty($_POST['save_key']) && !empty($_POST['token']) && !empty($_POST['bucket_name'])
    && !empty($_POST['access_key']) && !empty($_POST['secret_key']) && !empty($_POST['bucket_region'])) {
    auth_check($auth, 'w');
    if ($GLOBALS['is_admin'] === 'super') {
        $tmp = sql_fetch("select * from $table_name limit 1");

        $bucket_name = strip_tags(get_text(trim($_POST['bucket_name'])));

        $access_key = strip_tags(get_text(trim($_POST['access_key'])));

        $secret_key = strip_tags(get_text(trim($_POST['secret_key'])));

        $bucket_region = strip_tags(get_text(trim($_POST['bucket_region'])));
    }
}

if (!empty($_POST['save_status'] && !empty($_POST['token']) )) {
    auth_check($auth, 'w');
    if ($GLOBALS['is_admin'] === 'super') {
        $access_control_list = strip_tags(get_text(trim($_POST['access_control_list'])));
        if($access_control_list !== 'public-read'){ //유효성 검사
            $access_control_list = 'private';
        }

        $is_save_host = empty($_POST['access_control_list']) ? 0 : (int)strip_tags(get_text(trim(($_POST['is_save_host']))));
        if ($is_save_host !== 1) {
            $is_save_host = 0;
        }

        $is_only_use_s3 = empty($is_only_use_s3) ? 0 : (int)strip_tags(get_text(trim((($_POST['is_only_use_s3'])))));
        if ($is_save_host !== 1) {
            $is_save_host = 0;
        }

        if (function_exists('mysqli_query') && G5_MYSQLI_USE) {
            $sql_common = "
				SET acl_default_value = ?,
				is_save_host = ?,
				is_only_use_s3 = ?
				";

            if ($tmp) {
                $sql = "UPDATE $table_name $sql_common ";
                $res = sql_bind_query($sql, array('sii', $access_control_list, $is_save_host, $is_only_use_s3));
            } else {
                $sql = "INSERT INTO $table_name $sql_common ";
                $res = sql_bind_query($sql, array('sii', $access_control_list, $is_save_host, $is_only_use_s3));
            }

            if ($res->num_rows() === 1) {
                alert('저장되었습니다.');
            } else {
                //동일한 데이터가 들어올 경우는 직접체크
                $sql = "select count(*) from $table_name  where acl_default_value = ? and is_save_host = ? and is_only_use_s3 = ?";
                $res = sql_bind_query($sql, array('sii', $access_control_list, $is_save_host, $is_only_use_s3));
                $res->bind_result($count);
                while ($res->fetch()) {
                    $rows = $count;
                }
                if ($rows === 1) {
                    alert('저장되었습니다.');
                } else {
                    alert('저장에 실패했습니다.');
                }
            }
        } else {
//            mysqli 안쓰는 경우
            $sql_common = "
				SET acl_default_value = '{$access_control_list}',
				is_save_host = '{$is_save_host}',
				is_only_use_s3 = '{$is_only_use_s3}' ";
            if ($tmp) {
                $sql = "UPDATE $table_name $sql_common ";
            } else {
                $sql = "INSERT INTO $table_name $sql_common ";
            }
        }

        $result = sql_query($sql, false);
        if ($result) {
            alert('저장되었습니다.');
        } else {
            alert('저장에 실패했습니다.');
        }
    }
}

/**
 * 기존 DB 커넥션을 받아서 (mysqli_stmt_bind_param 함수받아서) prepared 쿼리문 실행
 *
 * @param $sql
 * @param array $args 배열의 첫번째는 mysqli_stmt_bind_param()의 타입, 나머지는 쿼리에 바인딩될 변수들
 * @return mysqli_stmt
 */
function sql_bind_query($sql, $args)
{
    if (!function_exists('mysqli_query')) {
        die('사용 불가');
    }
    global $g5;
    $link = $g5['connect_db'];
    $length = count($args);
    for ($i = 0; $i < $length; $i++) {
        /* with call_user_func_array, array params must be passed by reference */
        $params[] = &$args[$i];
    }
    /**
     * @var mysqli_stmt
     */
    $stmt = $link->prepare(trim($sql));
    if ($stmt->error_list) {
        error_log($stmt['error_list']);
    }

    /* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
    call_user_func_array(array($stmt, 'bind_param'), $params);

    $stmt->execute();
    return $stmt;
}


if (!empty($_POST['sync'])) {
    auth_check($auth, 'w');
    if ($GLOBALS['is_admin'] === 'super') {
        \Gnuboard\Plugin\AwsS3\S3Service::get_instance()->upload_test();
    }
}

// 관리자 페이지 aws s3 설정
?>
    <div class="aws-s3-area">
        <form name="f_s3_key" id="f_s3_key" method="post" onsubmit="return s3_key_submit(this);"
              autocomplete="off" role="presentation">
            <input type="hidden" name="save_key" value="save">
            <input type="hidden" name="token" value="" id="token">
            <button id="anc_cf_basic" type="button" class="tab_tit close">AWS S3 설정</button>
            <section class="tab_con">
                <h2 class="h2_frm">AWS S3 설정</h2>
                <ul class="frm_ul">
                    <li>
					<span class="lb_block"><label for="bucket_name">버킷이름</label>
					<?php
                    echo help('aws s3에서 버킷을 생성후에 버킷이름을 입력합니다.'); ?>
					</span>
                        <input type="text" name="bucket_name" autocomplete="new-password" value="<?php
                        echo $admin_aws_config['bucket_name']; ?>" id="bucket_name" class="frm_input" size="60">
                    </li>
                    <li>
					<span class="lb_block"><label for="bucket_region">리전</label>
					<?php
                    echo help('aws s3에서 버킷의 지역을 선택합니다.'); ?>
					</span>
                        <select name="bucket_region" id="bucket_region">
                            <?php
                            foreach ($all_regions as $k => $v) {
                                $selected = ($admin_aws_config['bucket_region'] === $k) ? 'selected="selected"' : '';
                                echo '<option value="' . $k . '" ' . $selected . ' >' . $v . '</option>';
                            }
                            ?>
                        </select>
                    </li>
                </ul>
                <ul class="frm_ul">
                    <li>
					<span class="lb_block"><label for="access_key">엑세스 키</label>
					</span>
                        <input type="text" autocomplete="new-password" name="access_key" value="<?php
                        echo $admin_aws_config['access_key']; ?>" id="access_key" class="frm_input" size="60">
                    </li>
                    <li>
					<span class="lb_block"><label for="secret_key">비밀키</label>
					</span>
                        <input type="password" autocomplete="new-password" name="secret_key" value="" id="secret_key"
                               class="frm_input"
                               size="60">
                    </li>
                </ul>
                <div class="">
                    <input type="submit" value="s3 설정" class="btn_submit btn" accesskey="s">
                </div>

            </section>
        </form>

        <form name="f_s3_state" id="f_s3_state" method="post" onsubmit="return s3_state_submit(this);"
              autocomplete="off" role="presentation">
            <section class="tab_con">
                <ul class="frm_ul">
                    <input type="hidden" name="save_status" value="save">
                    <input type="hidden" name="token" value="">
                    <li>
					<span class="lb_block"><label for="access_control_list">파일 권한 ACL</label>
					<?php
                    echo help(
                        '개별 파일의 권한을 설정합니다. 이미지,비디오 확장자파일( jpg, jpeg, png, gif, webp, bmp, mp4, webm ) 은 항상 공개이며 (public-read)
                        private 이면 그 밖에 다른 확장자파일은 private 권한이 부여됩니다. <br>public-read 이면 업로드 되는 모든 파일은 public-read 권한이 부여됩니다.'
                    ); ?>
					</span>
                        <?php
                        $aws_s3_acl = array('private', 'public-read'); ?>
                        <select name="access_control_list" id="access_control_list">
                            <?php
                            foreach ($aws_s3_acl as $v) {
                                $txt = ($v === 'private') ? 'private (이미지제외)' : $v;
                                $selected = ($admin_aws_config['access_control_list'] === $v) ? 'selected="selected"' : '';
                                echo '<option value="' . $v . '" ' . $selected . ' >' . $txt . '</option>';
                            }
                            ?>
                        </select>
                    </li>
                </ul>
                <ul class="frm_ul">
                    <li>
                        <span class="lb_block"><label for="is_only_use_s3">S3 사용하기</label>
                        <?php
                        echo help(
                            '내 서버 안에 모든 데이터를 aws s3 에 업로드 했고, 데이터를 aws s3 에만 저장 ( https://bucket_name.amazonaws.com/ ) 하고 싶다면 체크합니다.'
                        ); ?>
                        </span>
                            <input type="checkbox" name="is_only_use_s3" value="1"
                                   id="is_only_use_s3"
                                <?php
                                $is_use = $admin_aws_config['is_only_use_s3'];
                                echo $is_use == 1 ? 'checked="true"' : '' ?>
                            >
                        <label for="is_only_use_s3">
                                첨부된 데이터경로를 무조건 aws s3으로 할시에 체크
                        </label>
                    </li>
                </ul>
                <div class="">
                    <input type="submit" id="s3_state_submit" value="사용여부 및 권한 설정하기" class="btn_submit btn" accesskey="s">
                </div>
            </section>
        </form>
    </div>

    <script>
        //폼 재전송 방지
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        function s3_key_submit(f) {
            $result = confirm("s3 저장소를 새로 설정하시겠습니까?")
            if (!$result) {
                return false;
            }
            if (f.bucket_name.value === '') {
                alert('버킷 이름을 입력하십시오.');
                f.bucket_name.focus();
                return false;
            }
            if (f.access_key.value === '') {
                alert('엑세스키를 입력하십시오.');
                f.access_key.focus();
                return false;
            }
            if (f.secret_key.value === '') {
                alert('비밀 키를 입력하십시오.');
                f.secret_key.focus();
                return false;
            }

            return true;
        }

        function s3_state_submit(f) {
            return true;
        }

        function sync_onsubmit() {
            return true;
        }

        function disableF5(e) {
            if ((e.which || e.keyCode) === 116) e.preventDefault();
        }

        $(document).on("keydown", disableF5);

    </script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
