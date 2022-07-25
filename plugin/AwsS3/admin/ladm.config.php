<?php

if (!defined('_GNUBOARD_')) {
    exit;
} // 개별 페이지 접근 불가

//$path = dirname(__DIR__,3);

require_once(dirname(__FILE__).'/_common.php');
require_once(G5_ADMIN_PATH . '/admin.lib.php');
$admin_number = '100921';
$sub_menu = '100921';
//include_once (G5_LIB_PATH . '/AwsSdk/S3Service.php');
include_once(G5_ADMIN_PATH .'/admin.head.php');
//$k=auth_check($auth,'r',true);
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="' . G5_PLUGIN_URL . '/aws_s3/skin/adm.style.css">', 0);

include_once(G5_PLUGIN_PATH. '/AwsS3/S3Service.php');
$s3_config = array(
    's3_bucket_name' => '',
    's3_user_key' => '',
    's3_bucket_region' => ''
)
// 관리자 페이지 aws s3 설정
?>
<style>
    @import url('adm.style.css');
</style>
<div class="aws-s3-area">
    <form name="f_aws_s3_config" id="f_aws_s3_config" method="post" onsubmit="return f_aws_s3_config_submit(this);">
        <input type="hidden" name="post_action" value="save">
        <input type="hidden" name="token" value="" id="token">
        <button id="anc_cf_basic" type="button" class="tab_tit close">AWS S3 설정</button>
        <section class="tab_con">
            <h2 class="h2_frm">AWS S3 설정</h2>
            <ul class="frm_ul">
                <li>
					<span class="lb_block"><label for="s3_bucket_name">버킷이름</label>
					<?php
                    echo help('aws s3에서 버킷을 생성후에 버킷이름을 입력합니다.'); ?>
					</span>
                    <input type="text" name="s3_bucket_name" value="<?php
                    echo $s3_config['s3_bucket_name']; ?>" id="s3_bucket_name" class="frm_input" size="50">
                </li>
                <li>
					<span class="lb_block"><label for="s3_bucket_region">리전</label>
					<?php
                    echo help('aws s3에서 버킷의 리전을 선택합니다.'); ?>
					</span>
                    <select name="s3_bucket_region" id="s3_bucket_region">
                        <?php
                        foreach ($all_regions as $k => $v) {
                            $selected = ($s3_config['s3_bucket_region'] === $k) ? 'selected="selected"' : '';
                            echo '<option value="' . $k . '" ' . $selected . ' >' . $v . '</option>';
                        }
                        ?>
                    </select>
                </li>
            </ul>
            <ul class="frm_ul">
                <li>
					<span class="lb_block"><label for="s3_user_key">엑세스 키</label>
					</span>
                    <input type="text" name="s3_user_key" value="<?php
                    echo $s3_config['s3_user_key']; ?>" id="s3_user_key" class="frm_input" size="50">
                </li>
                <li>
					<span class="lb_block"><label for="s3_user_secret">비밀키</label>
					</span>
                    <input type="text" name="s3_user_secret" value=" " id="s3_user_secret" class="frm_input" size="50">
                </li>
            </ul>
            <ul class="frm_ul">
                <li>
					<span class="lb_block"><label for="s3_access_control_list">파일 ACL</label>
					<?php
                    echo help(
                        'private 이면 이미지 확장자파일( jpg, jpeg, png, gif, webp, bmp ) 은 public-read 가지며, 그 외 다른 확장자파일은 private 권한이 부여됩니다.<br>public-read 이면 업로드 되는 모든 파일은 public-read 권한이 부여됩니다.'
                    ); ?>
					</span>
                    <?php
                    $aws_s3_acl = array('private', 'public-read'); ?>
                    <select name="s3_access_control_list" id="s3_access_control_list">
                        <?php
                        foreach ($aws_s3_acl as $v) {
                            $txt = ($v === 'private') ? 'private (이미지제외)' : $v;
                            $selected = ($s3_config['s3_access_control_list'] === $v) ? 'selected="selected"' : '';
                            echo '<option value="' . $v . '" ' . $selected . ' >' . $txt . '</option>';
                        }
                        ?>
                    </select>
                </li>
            </ul>
            <ul class="frm_ul">
                <li>
					<span class="lb_block"><label for="s3_save_mydata">내 서버에 파일 저장 여부</label>
					<?php
                    echo help('내 서버 데이터 경로에도 첨부파일을 저장하려면 체크합니다.'); ?>
					</span>
                    <input type="checkbox" name="s3_save_mydata" value="1" id="s3_save_mydata" <?php
                    echo $s3_config['s3_save_mydata'] ? 'checked' : ''; ?>> <label for="s3_save_mydata">체크시 내서버에
                        저장</label>
                </li>
                <li>
					<span class="lb_block"><label for="s3_only_used_file">데이터 경로 표시 체크 여부</label>
					<?php
                    echo help(
                        '내 서버 안에 모든 데이터를 aws s3 에 업로드 했고, 무조건 데이터 경로를 aws s3 ( https://bucket_name.amazonaws.com/ ) 로 하고 싶다면 체크합니다.'
                    ); ?>
					</span>
                    <input type="checkbox" name="s3_only_used_file" value="1" id="s3_only_used_file" <?php
                    echo $s3_config['s3_only_used_file'] ? 'checked' : ''; ?>> <label for="s3_only_used_file">
                        첨부된 데이터경로를 무조건 aws s3으로 할시에 체크</label>
                </li>
            </ul>
        </section>

        <div class="btn_fixed_top btn_confirm">
            <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
        </div>
    </form>
</div>
<script>

    function f_aws_s3_config_submit(f) {
        return true;
    }
</script>

<?php
include_once('./admin.tail.php');
