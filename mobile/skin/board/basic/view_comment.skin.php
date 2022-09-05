<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<script>
// 글자수 제한
var char_min = parseInt(<?php echo $comment_min ?>); // 최소
var char_max = parseInt(<?php echo $comment_max ?>); // 최대
</script>

<!-- 댓글 리스트 -->
<section id="bo_vc">
    <h2 class="bo_vc_tit">댓글목록 <span><?php echo $view['wr_comment']; ?></span></h2>
    <?php
    for ($i=0; $i<count($list); $i++) {
        $comment_id = $list[$i]['wr_id'];
        $cmt_depth = ""; // 댓글단계
        $cmt_depth = strlen($list[$i]['wr_comment_reply']) * 15;
        $comment = $list[$i]['content'];
        $comment = html_purifier(htmlspecialchars_decode($comment));
        $comment = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $comment);
		$c_reply_href = $comment_common_url.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
		$c_edit_href = $comment_common_url.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
        $is_comment_reply_edit = ($list[$i]['is_reply'] || $list[$i]['is_edit'] || $list[$i]['is_del']) ? 1 : 0;
    ?>
    <article id="c_<?php echo $comment_id ?>" <?php if ($cmt_depth) { ?>style="margin-left:<?php echo $cmt_depth ?>px;border-bottom-color:#f8f8f8"<?php } ?>>
        <div class="comment_inner">
            <header>
                <h2><?php echo get_text($list[$i]['wr_name']); ?>님의 댓글<?php if ($cmt_depth) { ?><span class="sound_only">의 댓글</span><?php } ?></h2>
                <?php echo $list[$i]['name'] ?>
                <?php if ($is_ip_view) { ?>
                <span class="sound_only">아이피</span>
                <span class="bo_vc_hdinfo">(<?php echo $list[$i]['ip']; ?>)</span>
                <?php } ?>
                <span class="sound_only">작성일</span>
                <span class="bo_vc_hdinfo"><i class="fa fa-clock-o" aria-hidden="true"></i> <time datetime="<?php echo date('Y-m-d\TH:i:s+09:00', strtotime($list[$i]['datetime'])) ?>"><?php echo $list[$i]['datetime'] ?></time></span>
                <?php
                include(G5_SNS_PATH."/view_comment_list.sns.skin.php");
                ?>
                <?php if( $is_comment_reply_edit ){ ?>
                <div class="bo_vl_opt">
                    <button type="button" class="btn_cm_opt btn_b03 btn"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">댓글 옵션</span></button>
                    <ul class="bo_vc_act">
                        <?php if ($list[$i]['is_reply']) { ?><li><a href="<?php echo $c_reply_href; ?>" onclick="comment_box('<?php echo $comment_id ?>', 'c'); return false;">답변</a></li><?php } ?>
                        <?php if ($list[$i]['is_edit']) { ?><li><a href="<?php echo $c_edit_href; ?>" onclick="comment_box('<?php echo $comment_id ?>', 'cu'); return false;">수정</a></li><?php } ?>
                        <?php if ($list[$i]['is_del']) { ?><li><a href="<?php echo $list[$i]['del_link']; ?>" onclick="return comment_delete();">삭제</a></li><?php } ?>
                    </ul>
                </div>
                <div class="upload-file-list"></div>
                <?php } ?>
                <script>
                $(function() {
                    // 댓글 옵션창 열기
                    $(".btn_cm_opt").on("click", function(){
                        $(this).parent("div").children(".bo_vc_act").show();
                    });

                    // 댓글 옵션창 닫기
                    $(document).mouseup(function (e){
                        var container = $(".bo_vc_act");
                        if( container.has(e.target).length === 0)
                        container.hide();
                    });
                });
                </script>
            </header>
            <div class="cmt_contents">
                <!-- 댓글 출력 -->
                <p>
                    <?php if (strstr($list[$i]['wr_option'], "secret")) echo "<img src=\"".$board_skin_url."/img/icon_secret.gif\" alt=\"비밀글\">"; ?>
                    <?php echo comment_image_url_parser($comment); ?>
                </p>

                <?php if($list[$i]['is_reply'] || $list[$i]['is_edit'] || $list[$i]['is_del']) {
                    if($w == 'cu') {
                        $sql = " select wr_id, wr_content, mb_id from $write_table where wr_id = '$c_id' and wr_is_comment = '1' ";
                        $cmt = sql_fetch($sql);
                        if (isset($cmt)) {
                            if (!($is_admin || ($member['mb_id'] == $cmt['mb_id'] && $cmt['mb_id']))) {
                                $cmt['wr_content'] = '';
                            }
                            $c_wr_content = $cmt['wr_content'];
                        }
                        
                    }
                ?>
                <?php } ?>
            </div>
                <span id="edit_<?php echo $comment_id ?>"></span><!-- 수정 -->
                <span id="reply_<?php echo $comment_id ?>"></span><!-- 답변 -->
            <input type="hidden" id="secret_comment_<?php echo $comment_id ?>" value="<?php echo strstr($list[$i]['wr_option'],"secret") ?>">
<!--            <textarea id="save_comment_--><?php //echo $comment_id ?><!--" style="display:none">--><?php //echo get_text($list[$i]['content1'], 0) ?><!--</textarea>
-->
        </div>
    </article>
    <?php } ?>
    <?php if ($i == 0) { //댓글이 없다면 ?><p id="bo_vc_empty">등록된 댓글이 없습니다.</p><?php } ?>

</section>

<?php if ($is_comment_write) {
        if($w == '')
            $w = 'c';
    ?>
    <aside id="bo_vc_w">
        <h2>댓글쓰기</h2>
        <form name="fviewcomment" id="fviewcomment" action="<?php echo $comment_action_url; ?>" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off" class="bo_vc_w">
        <input type="hidden" name="w" value="<?php echo $w ?>" id="w">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
        <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
        <input type="hidden" name="comment_id" value="<?php echo $c_id ?>" id="comment_id">
        <input type="hidden" name="sca" value="<?php echo $sca ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
        <input type="hidden" name="stx" value="<?php echo $stx ?>">
        <input type="hidden" name="spt" value="<?php echo $spt ?>">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="is_good" value="">

        <?php if ($comment_min || $comment_max) { ?><strong id="char_cnt"><span id="char_count"></span>글자</strong><?php } ?>
        <div id="wr_content" class="comment_content" contenteditable='true' name="wr_content"  title="내용" placeholder="댓글내용을 입력해주세요"
        <?php if ($comment_min || $comment_max) { ?>onkeyup="check_byte('wr_content', 'char_count');"<?php } ?> ><?php echo $c_wr_content; ?></div>
        <?php if ($comment_min || $comment_max) { ?><script> check_byte('wr_content', 'char_count'); </script><?php } ?>
                
        <div class="bo_vc_w_wr">
            <div class="bo_vc_w_info">
                <?php if ($is_guest) { ?>
                <label for="wr_name" class="sound_only">이름<strong> 필수</strong></label>
                <input type="text" name="wr_name" value="<?php echo get_cookie("ck_sns_name"); ?>" id="wr_name" required class="frm_input required" size="25" placeholder="이름">
                <label for="wr_password" class="sound_only">비밀번호<strong> 필수</strong></label>
                <input type="password" name="wr_password" id="wr_password" required class="frm_input required" size="25"  placeholder="비밀번호">
                <?php
                }
                ?>
                <?php if ($is_guest) { ?>
                    <?php echo $captcha_html; ?>
                <?php } ?>
                <?php
                if($board['bo_use_sns'] && ($config['cf_facebook_appid'] || $config['cf_twitter_key'])) {
                ?>
                <span class="sound_only">SNS 동시등록</span>
                <span id="bo_vc_send_sns"></span>
                <?php } ?>

                <span class="bo_vc_secret chk_box">
                    <input type="checkbox" name="wr_secret" value="secret" id="wr_secret" class="selec_chk">
                    <label for="wr_secret" class="icon_lock">
                    	<span></span>비밀글
                    </label>
                </span>
            </div>
            <div class="btn_confirm">
                <button type="submit" id="btn_submit" class="btn_submit">댓글등록</button>
            </div>

        </div>
        </form>
    </aside>
    <form id="fcomment_file" action="<?php echo $comment_file_input_url?>" method='post' enctype="multipart/form-data">
        <input type="hidden" name="w" value="u">
        <input type="file" name="comment_file[]" multiple>
        <input type="button" id='btn_comment_file_send' name="file_send" value="업로드">
        <div class="upload-file-list"></div>
    </form>

    <script>
    var save_before = '';
    var save_html = document.getElementById('bo_vc_w').innerHTML;

    document.getElementById('wr_content').addEventListener('keypress', add_paragraph)

    function good_and_write()
    {
        let f = document.fviewcomment;
        if (fviewcomment_submit(f)) {
            f.is_good.value = 1;
            f.submit();
        } else {
            f.is_good.value = 0;
        }
    }

    function fviewcomment_submit(f)
    {
        let wr_content = document.querySelector('#wr_content').innerHTML;
        let input_wr_content = document.createElement('input');
        input_wr_content.type='hidden';
        input_wr_content.name='wr_content';
        input_wr_content.value = wr_content;
        $(f).prepend(input_wr_content);
        f.is_good.value = 0;

        /*
        var s;
        if (s = word_filter_check(document.getElementById('wr_content').value))
        {
            alert("내용에 금지단어('"+s+"')가 포함되어있습니다");
            document.getElementById('wr_content').focus();
            return false;
        }
        */

        var subject = "";
        var content = "";
        $.ajax({
            url: g5_bbs_url + "/ajax.filter.php",
            type: "POST",
            data: {
                "subject": "",
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (content) {
            alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
            f.wr_content.focus();
            return false;
        }

        // 양쪽 공백 없애기
        var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자
        document.getElementById('wr_content').value = document.getElementById('wr_content').value.replace(pattern, "");
        if (char_min > 0 || char_max > 0)
        {
            check_byte('wr_content', 'char_count');
            var cnt = parseInt(document.getElementById('char_count').innerHTML);
            if (char_min > 0 && char_min > cnt)
            {
                alert("댓글은 "+char_min+"글자 이상 쓰셔야 합니다.");
                return false;
            } else if (char_max > 0 && char_max < cnt)
            {
                alert("댓글은 "+char_max+"글자 이하로 쓰셔야 합니다.");
                return false;
            }
        }
        else if (!document.getElementById('wr_content').innerHTML)
        {
            alert("댓글을 입력하여 주십시오.");
            return false;
        }

        if (typeof(f.wr_name) != 'undefined')
        {
            f.wr_name.value = f.wr_name.value.replace(pattern, "");
            if (f.wr_name.value == '')
            {
                alert('이름이 입력되지 않았습니다.');
                f.wr_name.focus();
                return false;
            }
        }

        if (typeof(f.wr_password) != 'undefined')
        {
            f.wr_password.value = f.wr_password.value.replace(pattern, "");
            if (f.wr_password.value == '')
            {
                alert('비밀번호가 입력되지 않았습니다.');
                f.wr_password.focus();
                return false;
            }
        }

        <?php if($is_guest) echo chk_captcha_js(); ?>

        set_comment_token(f);

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }

    function comment_box(comment_id, work)
    {
        var el_id,
        form_el = 'fviewcomment',
        respond = document.getElementById(form_el);

        // 댓글 아이디가 넘어오면 답변, 수정
        if (comment_id)
        {
            if (work == 'c')
                el_id = 'reply_' + comment_id;
            else
                el_id = 'edit_' + comment_id;
        }
        else
            el_id = 'bo_vc_w';

        if (save_before != el_id)
        {
            if (save_before)
            {
                document.getElementById(save_before).style.display = 'none';
            }

            document.getElementById(el_id).style.display = '';
            document.getElementById(el_id).appendChild(respond);
            //입력값 초기화
            document.getElementById('wr_content').value = '';

            // 댓글 수정
            if (work === 'cu') {
                document.getElementById('wr_content').innerHTML = document.querySelector('#c_' + comment_id + ' .cmt_contents').innerHTML;
                document.getElementById('wr_content').addEventListener('keypress', add_paragraph)

                let file_uploader = document.getElementById('fcomment_file')
                file_uploader.reset();
                file_uploader.querySelector('.upload-file-list').innerHTML = '';
                document.getElementById(el_id).appendChild(file_uploader);

                load_comment_file(g5_bo_table, comment_id);

                if (typeof char_count != 'undefined')
                    check_byte('wr_content', 'char_count');
                if (document.getElementById('secret_comment_' + comment_id).value)
                    document.getElementById('wr_secret').checked = true;
                else
                    document.getElementById('wr_secret').checked = false;
            }

            document.getElementById('comment_id').value = comment_id;
            document.getElementById('w').value = work;

            if(save_before)
                $("#captcha_reload").trigger("click");

            save_before = el_id;
        }
    }

    function comment_delete() {
        return confirm("이 댓글을 삭제하시겠습니까?");
    }

    function comment_file_submit() {
        let form = document.querySelector('#fcomment_file');
        set_comment_token(form);
        let formData = new FormData(form);
        formData.append('bo_table', g5_bo_table);

        $.ajax({
            url: g5_bbs_url + "/comment_file.php",
            type: 'post',
            enctype: 'multipart/form-data',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            success: function (data) {
                let res = JSON.parse(data)
                view_image(res);
                let file_list = create_comment_file_list(res)
                document.querySelector('#fcomment_file .upload-file-list').appendChild(file_list)
            },
            error: function (e) {
                let res = JSON.parse(e.responseText)
                let error_files = res.error_file_list;
                if(typeof res.allow_file_size){
                    alert(res.msg + error_files + ' ' + res.allow_file_size + ' 이하로 확인해 주세요')
                }
                alert(res.msg + error_files)
            }
        });

    }

    function view_image(data) {
        let image = ''
        let comment_content = document.querySelector('#wr_content');
        let image_temp_wrapper = document.createDocumentFragment();
        let regexImageExtension = /(png|jpg|jpeg|gif|webp)/i;
        let newLine = document.createElement('br');

        for (let i = 0; i < data['files'].length; i++) {
            if (data['files'][i]['file_type'].search(regexImageExtension) === -1) {
                continue;
            }
            let image_container_div = document.createElement('div');
            //image_container_div.className = '' 클래스이름 지정
            image = document.createElement('img');
            image.src = data['files'][i]['end_point'] + '/' + data['files'][i]['file_name'];
            image.alt = data['files'][i]['original_name'];
            image.id = 'comment_img';

            image_container_div.appendChild(image);
            image_temp_wrapper.appendChild(image_container_div);
            image_temp_wrapper.appendChild(newLine);
        }

        if (image_temp_wrapper.hasChildNodes()) {
            comment_content.appendChild(image_temp_wrapper);
        }
    }

    function add_paragraph(e) {
        if (e.keyCode == '13') {
            document.execCommand('formatBlock', false, 'p');
        }
    }

    function create_comment_file_list(data) {
        let template = document.createDocumentFragment();
        for (let i = 0; i < data['files'].length; i++) {
            let file_data = data['files'][i];
            let files_div = document.createElement('div');
            files_div.className = 'write_div file_wr'
            files_div.type = 'button'
            files_div.value = file_data['file_name'];

            let icon_tag = document.createElement('i');
            icon_tag.className = 'lb_icon fa fa-folder-open';
            icon_tag.setAttribute('aria-hidden', 'true');

            let icon_tag_span = document.createElement('span');
            icon_tag_span.className = 'sound_only';
            icon_tag_span.innerText = '파일';

            let file_name_span = document.createElement('span');
            file_name_span.className = 'form_file';
            file_name_span.innerText = file_data['original_name'];

            icon_tag.appendChild(icon_tag_span);
            files_div.appendChild(icon_tag);
            files_div.appendChild(file_name_span);

            let delete_file_button = document.createElement('input');
            delete_file_button.type = 'button'
            delete_file_button.className = 'btn_delete_file';
            delete_file_button.value = '파일 삭제';
            delete_file_button.addEventListener('click', function (e) {
                delete_comment_file(e, file_data);
            })

            files_div.appendChild(delete_file_button);
            template.appendChild(files_div)
        }
        return template;
    }

    function create_download_file_list(data) {
        let file_list = document.createDocumentFragment();
        let ul = document.createElement('ul');
        for (let i = 0; i < data['files'].length; i++) {
            let file_data = data['files'][i];
            let li = document.createElement('li');
            li.className = 'file-item';

            let icon_tag = document.createElement('i');
            icon_tag.className = 'fa fa-folder-open';
            let file_info_wrapper = document.createElement('div');
            file_info_wrapper.className = 'file-info-wrapper';

            let link = document.createElement('a');
            let file_title = document.createElement('strong');
            file_title.innerText = file_data['original_name'];
            link.appendChild(file_title);
            link.innerText += ' (' + parseFloat(file_data['file_size'] / 1024).toFixed(2) + 'K)';

            link.addEventListener('click', function (e) {
                download_comment_file(file_data)
                console.log(file_data)
            })

            let file_info_span = document.createElement('span');
            file_info_span.innerText = file_data['file_download_count'] + ' 회 다운로드 | ' + 'DATE: ' + file_data['save_time'];

            file_info_wrapper.appendChild(link);
            file_info_wrapper.appendChild(file_info_span);

            li.appendChild(icon_tag);
            li.appendChild(file_info_wrapper);

            ul.appendChild(li);

        }
        return file_list.appendChild(ul);
    }

    /**
     * 댓글 보기의 첨부파일 출력
     * @param data
     */
    function show_comment_files(data) {
        let data_array = [];
        for (let i in data) { //js obj -> array ie 11
            if (data.hasOwnProperty(i)) {
                data_array.push(i);
            }
        }
        for (let i = 0; i < data_array.length; i++) {
            let index = data_array[i];
            let comment_id_selector = '#c_' + index + ' .upload-file-list';
            document.querySelector(comment_id_selector).appendChild(create_download_file_list(data[index]));
        }
    }

    function add_comment_file_event() {
        document.querySelector('#btn_comment_file_send').addEventListener('click', function (e) {
            e.preventDefault();
            comment_file_submit();
        });
    }

    function delete_comment_file(e, file_info) {
        $(e.target.parentElement).remove();
        let form = document.getElementById('wr_content');
        let images = form.querySelectorAll('img');
        let image_name = '';
        for (let i = 0; i < images.length; i++) {
            image_name = images[i].src.split('/').pop();
            if (image_name === file_info['file_name']) {
                $(images[i]).remove();
            }
        }

        let result = '';
        let data = {
            token: get_comment_token(),
            file_name: file_info['file_name'],
            bo_table: g5_bo_table,
            comment_id: file_info['comment_id'],
            w: 'd'
        }
        $.ajax({
            url: g5_bbs_url + "/comment_file.php",
            type: 'post',
            data: JSON.stringify(data),
            contentType: false,
            cache: false,
            success: function (data) {
                result = JSON.parse(data)
            },
            error: function (e) {
                result = JSON.parse(e);
                alert(e.msg);
            }
        });
        return result;
    }

    /**
     * 수정 중인 댓글의 첨부파일 불러오기
     * @param bo_table
     * @param comment_id
     */
    function load_comment_file(bo_table, comment_id) {
        let data = {
            w: 'r',
            bo_table: bo_table,
            comment_id: comment_id,
            token: get_comment_token()
        }

        $.ajax(g5_bbs_url + "/comment_file.php", {
            type: 'post',
            data: JSON.stringify(data),
            contentType: false,
            cache: false,
            success: function (data) {
                let result = JSON.parse(data)
                let file_list = create_comment_file_list(result)
                let comment_id_selector = '#c_' + comment_id + ' > .upload-file-list';
                document.querySelector(comment_id_selector).innerHTML = '';
                let upload_file_section = document.querySelector('#fcomment_file .upload-file-list');
                upload_file_section.appendChild(file_list);
            }
        })
    }

    function load_comment_file_list(bo_table, wr_id) {
        let data = {
            w: 'a',
            bo_table: bo_table,
            wr_id: wr_id,
            token: get_comment_token()
        }

        $.ajax(g5_bbs_url + "/comment_file.php", {
            type: 'post',
            data: JSON.stringify(data),
            contentType: false,
            success: function (data) {
                let result = JSON.parse(data)
                if (typeof result.files !== "undefined") {
                    return;
                } else {
                    show_comment_files(result);
                }
            },
            error: function (e) {

            }
        })
    }

    /**
     * 비동기 파일 다운로드
     * @param file_data
     */
    function download_comment_file(file_data) {
        let data = {
            w: 'download',
            bo_table: g5_bo_table,
            comment_id: file_data['comment_id'],
            file_name: file_data['file_name'],
            token: get_comment_token()
        }

        let url = g5_bbs_url + '/comment_file.php';
        let req = new XMLHttpRequest();
        req.open('POST', url, true);
        req.send(JSON.stringify(data));
        req.responseType = 'blob';
        req.onreadystatechange = function () {
            if (req.readyState === 4) {
                ajax_success(req.response, file_data['original_name'])
            }
        }

        function ajax_success(data, file_name) {
            let blob = new Blob([data])

            if (window.navigator && window.navigator.msSaveOrOpenBlob) { //IE 11
                window.navigator.msSaveOrOpenBlob(blob, file_name);
            } else {
                let link = document.createElement('a');
                link.download = file_name;
                link.href = window.URL.createObjectURL(blob);
                link.click();
                window.URL.revokeObjectURL(link.href)
            }
        }
    }

    comment_box('', 'c'); // 댓글 입력폼이 보이도록 처리하기위해서 추가 (root님)

    <?php if($board['bo_use_sns'] && ($config['cf_facebook_appid'] || $config['cf_twitter_key'])) { ?>
    $(function() {
    // sns 등록
        $("#bo_vc_send_sns").load(
            "<?php echo G5_SNS_URL; ?>/view_comment_write.sns.skin.php?bo_table=<?php echo $bo_table; ?>",
            function() {
                save_html = document.getElementById('bo_vc_w').innerHTML;
            }
        );
           
    });
    <?php } ?>

    $(function() {            
        //댓글열기
        $(".cmt_btn").click(function(){
            $(this).toggleClass("cmt_btn_op");
            $("#bo_vc").toggle();
        });
        add_comment_file_event();
        load_comment_file_list(g5_bo_table, <?= $wr_id?> );
    });
    </script>
    <?php }
