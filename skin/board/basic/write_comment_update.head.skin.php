<?php

use Merry\plugin\CommentFileUploader as uploader;

$wr_content = isset($_POST['wr_content']) ? uploader\change_img_tag_comment_file(stripcslashes($wr_content)) : '';