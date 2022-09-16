<?php

if (!defined('_GNUBOARD_')) {
    exit;
} // 개별 페이지 접근 불가

/*
    환경설정에서 에디터 선택이 없는 경우에 사용하는 기본 에디터 입니다.
    에디터 선택시 "선택없음"이 아닌 경우 plugin/editor 하위 디렉토리의 각 에디터이름/editor.lib.php 를 수정하시기 바랍니다.
*/
include_once (G5_LIB_PATH . '/Nonce.php');
function default_editor_html($id, $content)
{
    $content = html_purifier(htmlspecialchars_decode($content));
    $nonce = Nonce::get_instance();
    $nonce->ft_nonce_create('editor_image_upload');
    $editor = <<<HERE
    <div contenteditable='true' id="{$id}" name="$id" class='normal_editor' style="width:100%;">$content</div>
    <script>
        add_editor_event();

        function add_editor_event() {
            let editor = document.querySelector('#wr_content');
            editor.addEventListener('keypress', add_paragraph);
            editor.addEventListener("paste", image_paste_uploader);
            editor.addEventListener('drop', image_drop, false);
        }

        function add_paragraph(e) {
            if (e.keyCode == '13') {
                document.execCommand('formatBlock', false, 'p');
            }
        }

        function html_decode(tag){
             tag.innerHTML = tag.innerText;
        }

        function image_drop(event) {
            event.preventDefault();
            console.log(event.dataTransfer.files[0]);
            let file_data = event.dataTransfer.files[0];
            if (file_data !== undefined) {
                file_submit(file_data);
            }
        }

        function image_paste_uploader(event) {
            console.log('eee')
            let items = (event.clipboardData || event.originalEvent.clipboardData).items;
            if (items[0] == undefined) {
                event.preventDefault();
                return;
            }
            if (items[0].type.includes('image')) {
                event.preventDefault();
                console.log('image')
            }

            let blob = items[0].getAsFile();
            if (blob == null) {
                if (items[1] == undefined) {
                    return;
                }
                if (items[1].type.includes('image')) {
                    event.preventDefault();
                    blob = items[1].getAsFile();
                }
            }

            let blobs = [blob];
            if (blobs[0] == null) {
                return;
            }

            file_submit(blob);
        }

        function file_submit(data){
            let editor = document.querySelector('#wr_content');
            let progressbar = document.createElement('progress');
            progressbar.max = 100;
            editor.appendChild(progressbar);

            let upload_percent = 0;
            let req = new XMLHttpRequest();
            req.upload.onprogress = function (e) {
                if (e.lengthComputable) {
                    upload_percent = Math.round(e.loaded / e.total * 100);
                    progressbar.value = upload_percent
                    console.log(upload_percent)
                    if (upload_percent == 100) {
                        progressbar.style.display = 'none';
                    }
                }
            }

            let url = g5_bbs_url + "/ajax.image.uploader.php";
            req.open('POST', url, true);
            let formData = new FormData();
            formData.append('bo_table', g5_bo_table)
            formData.append('file[]', data);
            formData.append('w', 'u'); //upload
            req.send(formData);
            req.onreadystatechange = function () {
                if (req.readyState === 4)  {

                    if(this.status == 200) {
                        upload_success(req.response);
                    } else {
                        let result = JSON.parse(req.response)
                        if(result == null){
                            alert('업로드에 실패했습니다.')
                        } else if(result.allow_file_size != undefined) {
                            alert('파일 크기는 ' + result.allow_file_size + ' MB 이하만 허용됩니다')
                        } else {
                            alert(result.msg);
                        }
                    }
                }
            }

            function upload_success(data) {
                let res = JSON.parse(data)
                view_image(res);
            }
        }

        function view_image(data) {
            if(data['files'] === undefined){
                return;
            }

            console.log(data)
            let image = ''
            let editor = document.querySelector('#wr_content');
            let image_temp_wrapper = document.createDocumentFragment();
            let regexImageExtension = /(png|jpg|jpeg|gif|webp)/i;
            let newLine = document.createElement('br');

            for (let i = 0; i < data['files'].length; i++) {
                if(data['files'][i]['file_type'] == null) {
                    continue;
                }
                if (data['files'][i]['file_type'].search(regexImageExtension) === -1) {
                    continue;
                }
                let image_container_div = document.createElement('div');
                image_container_div.className = 'img_wrapper'
                //image_container_div.className = '' 클래스이름 지정
                image = document.createElement('img');
                image.src = data['files'][i]['end_point'] + '/' + data['files'][i]['file_name'];
                image.alt = data['files'][i]['original_name'];

                image_container_div.appendChild(image);
                image_temp_wrapper.appendChild(image_container_div);
                image_temp_wrapper.appendChild(newLine);
            }

            if (image_temp_wrapper.hasChildNodes()) {
                editor.appendChild(image_temp_wrapper);
            }
        }
    </script>

HERE;
//5.x 는 HEREDOC 을 붙여쓴다.
    return $editor;
}


// textarea 로 값을 넘긴다. javascript 반드시 필요
function default_get_editor_js($id)
{
    return "
        let hidden_textarea  = document.createElement('textarea');
        hidden_textarea.name = '{$id}';
        hidden_textarea.style = 'visibility: hidden';
        hidden_textarea.value = document.querySelector('#{$id}').innerHTML;
        f.append(hidden_textarea);
    ";
    //업로드용 textarea
}


//  textarea 의 값이 비어 있는지 검사
function default_chk_editor_js($id)
{
    $editor_area_check_script =  "{$id}.value = document.querySelector('#{$id}').innerHTML";
    return $editor_area_check_script;
}