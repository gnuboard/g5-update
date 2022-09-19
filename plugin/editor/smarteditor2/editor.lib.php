<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH . '/Nonce.php');  //NONCE



function editor_html($id, $content, $is_dhtml_editor=true)
{
    global $g5, $config, $w, $board, $write;
    static $js = true;
    $nonce = Nonce::get_instance();
    $nonce->ft_nonce_create('editor_image_upload');

    if( 
        $is_dhtml_editor && $content && 
        (
        (!$w && (isset($board['bo_insert_content']) && !empty($board['bo_insert_content'])))
        || ($w == 'u' && isset($write['wr_option']) && strpos($write['wr_option'], 'html') === false )
        )
    ){       //글쓰기 기본 내용 처리
        if( preg_match('/\r|\n/', $content) && $content === strip_tags($content, '<a><strong><b>') ) {  //textarea로 작성되고, html 내용이 없다면
            $content = nl2br($content);
        }
    }

    $editor_url = G5_EDITOR_URL.'/'.$config['cf_editor'];

    $html = "";
    $html .= "<span class=\"sound_only\">웹에디터 시작</span>";
    if ($is_dhtml_editor)

        $html .= '<script>document.write("<div class=\'cke_sc\'><button type=\'button\' class=\'btn_cke_sc\'>단축키 일람</button></div>");</script>';

    if ($is_dhtml_editor && $js) {
        $html .= "\n".'<script src="'.$editor_url.'/js/service/HuskyEZCreator.js"></script>';
        $html .= "\n".'<script>var g5_editor_url = "'.$editor_url.'", oEditors = [], ed_nonce = "'.ft_nonce_create('smarteditor').'";</script>';
        $html .= "\n".'<script src="'.$editor_url.'/config.js"></script>';
        $html .= "\n<script>";
        $html .= '
        $(function(){
            $(".btn_cke_sc").click(function(){
                if ($(this).next("div.cke_sc_def").length) {
                    $(this).next("div.cke_sc_def").remove();
                    $(this).text("단축키 일람");
                } else {
                    $(this).after("<div class=\'cke_sc_def\' />").next("div.cke_sc_def").load("'.$editor_url.'/shortcut.html");
                    $(this).text("단축키 일람 닫기");
                }
            });
            $(document).on("click", ".btn_cke_sc_close", function(){
                $(this).parent("div.cke_sc_def").remove();
            });
        });

        

';
        $here = <<<HERE
        $(document).ready(function(){
        let editor_input_area = [];
            let editor_iframe = document.querySelectorAll('#smart_editor2')
            for(let i = 0; i < editor_iframe.length; i++){

            editor_iframe[i].onload = function(){
                let inner_iframe = editor_iframe[i].contentDocument.querySelector('iframe')
                inner_iframe.onload = function(){
                    let input_area = inner_iframe.contentDocument.querySelector('.se2_inputarea')
                    editor_input_area[i] = input_area;
                    
                    //에디터와 이벤트 등록
                    input_area.addEventListener("paste", function(event){
                        image_paste_uploader(event, editor_input_area[i]);
                    });
                    
                    input_area.addEventListener("drop", function(event){
                        image_drop(event, editor_input_area[i]);
                    });
                    
                }
                
            }
          }
          
            function image_paste_uploader(event, target_tag) {

                let items = (event.clipboardData || event.originalEvent.clipboardData).items;
                if (items[0] == undefined) {
                    event.preventDefault();
                    return;
                }
                if (items[0].type.includes('image')) {
                    event.preventDefault();

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
                
                file_submit(blob, target_tag);
            }
        
            function file_submit(data, target_tag){
                let editor = target_tag
                let progressbar = document.createElement('progress');
                progressbar.max = 100;
                editor.appendChild(progressbar);
    
                let upload_percent = 0;
                let req = new XMLHttpRequest();
                req.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        upload_percent = Math.round(e.loaded / e.total * 100);
                        progressbar.value = upload_percent
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
                    view_image(res, target_tag);
                }
            }
    
            function view_image(data, target_tag) {
                if(data['files'] === undefined){
                    return;
                }

                let image = ''
                let editor = target_tag;
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
            
            function image_drop(event, editor) {
                event.preventDefault();
                let file_data = event.dataTransfer.files[0];

                if (file_data !== undefined) {
                    file_submit(file_data, event.target);
                }
            }
        
        })

HERE;
        $html .=$here;
        $html .= "\n</script>";
        $js = false;
    }

    $smarteditor_class = $is_dhtml_editor ? "smarteditor2" : "";
    $html .= "\n<textarea id=\"$id\" name=\"$id\" class=\"$smarteditor_class\" maxlength=\"65536\" style=\"width:100%;height:300px\">$content</textarea>";
    $html .= "\n<span class=\"sound_only\">웹 에디터 끝</span>";
    return $html;
}


// textarea 로 값을 넘긴다. javascript 반드시 필요
function get_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "var {$id}_editor_data = oEditors.getById['{$id}'].getIR();\noEditors.getById['{$id}'].exec('UPDATE_CONTENTS_FIELD', []);\nif(jQuery.inArray(document.getElementById('{$id}').value.toLowerCase().replace(/^\s*|\s*$/g, ''), ['&nbsp;','<p>&nbsp;</p>','<p><br></p>','<div><br></div>','<p></p>','<br>','']) != -1){document.getElementById('{$id}').value='';}\n";
    } else {
        return "var {$id}_editor = document.getElementById('{$id}');\n";
    }
}


//  textarea 의 값이 비어 있는지 검사
function chk_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "if (!{$id}_editor_data || jQuery.inArray({$id}_editor_data.toLowerCase(), ['&nbsp;','<p>&nbsp;</p>','<p><br></p>','<p></p>','<br>']) != -1) { alert(\"내용을 입력해 주십시오.\"); oEditors.getById['{$id}'].exec('FOCUS'); return false; }\n";
    } else {
        return "if (!{$id}_editor.value) { alert(\"내용을 입력해 주십시오.\"); {$id}_editor.focus(); return false; }\n";
    }
}

/*
https://github.com/timostamm/NonceUtil-PHP
*/

if (!defined('FT_NONCE_UNIQUE_KEY'))
    define( 'FT_NONCE_UNIQUE_KEY' , sha1($_SERVER['SERVER_SOFTWARE'].G5_MYSQL_USER.session_id().G5_TABLE_PREFIX) );

if (!defined('FT_NONCE_SESSION_KEY'))
    define( 'FT_NONCE_SESSION_KEY' , substr(md5(FT_NONCE_UNIQUE_KEY), 5) );

if (!defined('FT_NONCE_DURATION'))
    define( 'FT_NONCE_DURATION' , 60 * 60 ); // 300 makes link or form good for 5 minutes from time of generation,  300은 5분간 유효, 60 * 60 은 1시간

if (!defined('FT_NONCE_KEY'))
    define( 'FT_NONCE_KEY' , '_nonce' );

// This method creates a key / value pair for a url string
if(!function_exists('ft_nonce_create_query_string')){
    function ft_nonce_create_query_string( $action = '' , $user = '' ){
        return FT_NONCE_KEY."=".ft_nonce_create( $action , $user );
    }
}

if(!function_exists('ft_get_secret_key')){
    function ft_get_secret_key($secret){
        return md5(FT_NONCE_UNIQUE_KEY.$secret);
    }
}

// This method creates an nonce. It should be called by one of the previous two functions.
if(!function_exists('ft_nonce_create')){
    function ft_nonce_create( $action = '',$user='', $timeoutSeconds=FT_NONCE_DURATION ){

        $secret = ft_get_secret_key($action.$user);

        set_session('token_'.FT_NONCE_SESSION_KEY, $secret);

		$salt = ft_nonce_generate_hash();
		$time = time();
		$maxTime = $time + $timeoutSeconds;
		$nonce = $salt . "|" . $maxTime . "|" . sha1( $salt . $secret . $maxTime );
		return $nonce;

    }
}

// This method validates an nonce
if(!function_exists('ft_nonce_is_valid')){
    function ft_nonce_is_valid( $nonce, $action = '', $user='' ){

        $secret = ft_get_secret_key($action.$user);

        $token = get_session('token_'.FT_NONCE_SESSION_KEY);

        if ($secret != $token){
            return false;
        }

		if (is_string($nonce) == false) {
			return false;
		}
		$a = explode('|', $nonce);
		if (count($a) != 3) {
			return false;
		}
		$salt = $a[0];
		$maxTime = (int)$a[1];
		$hash = $a[2];
		$back = sha1( $salt . $secret . $maxTime );
		if ($back != $hash) {
			return false;
		}
		if (time() > $maxTime) {
			return false;
		}
		return true;
    }
}

// This method generates the nonce timestamp
if(!function_exists('ft_nonce_generate_hash')){
    function ft_nonce_generate_hash(){
		$length = 10;
		$chars='1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
		$ll = strlen($chars)-1;
		$o = '';
		while (strlen($o) < $length) {
			$o .= $chars[ rand(0, $ll) ];
		}
		return $o;
    }
}