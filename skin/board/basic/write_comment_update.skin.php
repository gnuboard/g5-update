<?php

use Merry\plugin\CommentFileUploader as uploader;
//코멘트 수정, 입력 쿼리문 직후의 $comment_id 를 가져오기때문에
//상단에 있어야합니다.

//$upload_file_list 은 스킨의 사용자 입력값 입니다.
uploader\update_comment_file_db($upload_file_list, $bo_table, $comment_id);
