<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

class G5UpdateTest extends G5Update
{
    public function __construct()
    {
        
    }
    
    /**
     * 원본파일 / 패치파일 목록 출력
     * - step 2에서 버전파일이 만들어진다음 사용
     */
    public function viewOriginAndCompareFileList($version, $list) {

        foreach ($list as $key => $var) {
            echo G5_PATH . '/' . $var;
            if (file_exists(G5_PATH . '/' . $var)) {
                echo "<font style=\"color:blue\">true</font>";
            } else {
                echo "<font style=\"color:red\">false</font>";
            }
            echo "<br>"; 
            echo G5_DATA_PATH . '/update/version/' . $version . '/' . $var;
            if (file_exists(G5_DATA_PATH . '/update/version/' . $version . '/' . $var)) {
                echo "<font style=\"color:blue\">true</font>";
            } else {
                echo "<font style=\"color:red\">false</font>";
            }
            echo "<br><br>";
        }
    }
}
