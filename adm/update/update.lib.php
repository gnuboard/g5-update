<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

class G5Update
{
    private $g5_update;

    public $path = null;
    public $latest_version = null;
    public $target_version = null;
    public $rollback_version = null;
    public $now_version = null;

    static $dir_update     = G5_DATA_PATH . "/update";
    static $dir_version    = G5_DATA_PATH . "/update/version";
    static $dir_log        = G5_DATA_PATH . "/update/log";
    static $dir_backup     = G5_DATA_PATH . "/update/backup";

    // token값이 없는 경우, 1시간에 60번의 데이터조회가 가능함
    private $token = "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";

    private $url = "https://api.github.com";
    private $version_list = array();
    private $compare_list = array();
    private $backup_list = array();
    private $log_list = array();

    public $patch = array();

    private $username;

    private $conn;
    private $port;
    private $connPath;

    private $log_page_size = 10;
    private $log_page_list = 10;

    public function __construct()
    {
    }

    /**
     * FTP/SSH 연결
     * @breif 
     * @param string $hostname      접속할 host
     * @param string $port          접속 프로토콜 ("ftp", "sftp")
     * @param string $username      사용자 이름
     * @param string $userPassword  사용자 비밀번호
     * @return bool
     */
    public function connect($hostname, $port, $username, $userPassword)
    {
        $this->port = $port;

        if ($port == "ftp") {
            if (function_exists("ftp_connect")) {
                $this->conn = @ftp_connect($hostname, 21, 5);
                if ($this->conn == false) {
                    return false;
                }

                $login = ftp_login($this->conn, $username, $userPassword);
                if ($login == false) {
                    return false;
                }

                $this->username = $username;

                ftp_pasv($this->conn, true);

                return true;
            }
        } elseif ($port == "sftp") {
            if (function_exists("ssh2_connect")) {
                if ($this->conn != false) {
                    return true;
                }
                $this->conn = @ssh2_connect($hostname, 22);

                if ($this->conn == false) {
                    return false;
                }
                if (!ssh2_auth_password($this->conn, $username, $userPassword)) {
                    return false;
                }

                $this->username = $username;
                $this->connPath = @ssh2_sftp($this->conn);
                
                if (!$this->connPath) {
                    $this->conn = false;
                    $this->conPath = false;

                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * FTP/SSH 연결해제
     * @breif 
     * @return bool
     */
    public function disconnect()
    {
        if ($this->port == 'ftp') {
            ftp_close($this->conn);
            $this->connPath = null;
        } elseif ($this->port == 'sftp') {
            ssh2_disconnect($this->conn);
            $this->connPath = null;
        } else {
            return false;
        }

        return true;
    }

    public function getConn()
    {
        return $this->conn;
    }
    /**
     * 버전업데이트 경로 생성 및 권한처리
     * @brief
     * @todo
     * 1. 접속환경에 따라 경로가 달라진다고 했었는데 확인 필요함.
     * @return bool
     */
    public function makeUpdateDir()
    {
        try {
            if ($this->port == false) {
                throw new Exception("프로토콜을 확인 할 수 없습니다.");
            }
            if ($this->conn == false) {
                throw new Exception("연결된 프로토콜을 찾을 수 없습니다.");
            }

            if ($this->port == 'ftp') {
                $update_ftp_dir = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', self::$dir_update);

                if (!is_dir(self::$dir_update)) {
                    if (!ftp_mkdir($this->conn, $update_ftp_dir)) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ftp_chmod($this->conn, 0707, $update_ftp_dir)) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_version)) {
                    if (!ftp_mkdir($this->conn, $update_ftp_dir . '/version')) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ftp_chmod($this->conn, 0707, $update_ftp_dir . '/version')) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_log)) {
                    if (!ftp_mkdir($this->conn, $update_ftp_dir . '/log')) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ftp_chmod($this->conn, 0707, $update_ftp_dir . '/log')) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_backup)) {
                    if (!ftp_mkdir($this->conn, $update_ftp_dir . '/backup')) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ftp_chmod($this->conn, 0707, $update_ftp_dir . '/backup')) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }
                $list = ftp_nlist($this->conn, $update_ftp_dir);

            } elseif ($this->port == 'sftp') {
                if (!is_dir(self::$dir_update)) {
                    if (!ssh2_sftp_mkdir($this->connPath, self::$dir_update, 0707)) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, self::$dir_update, 0707)) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_version)) {
                    if (!ssh2_sftp_mkdir($this->connPath, self::$dir_version, 0707)) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, self::$dir_version, 0707)) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_log)) {
                    if (!ssh2_sftp_mkdir($this->connPath, self::$dir_log, 0755)) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, self::$dir_log, 0755)) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir(self::$dir_backup)) {
                    if (!ssh2_sftp_mkdir($this->connPath, self::$dir_backup, 0707)) {
                        throw new Exception("디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, self::$dir_backup . '/backup', 0707)) {
                        throw new Exception("디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }
            } else {
                throw new Exception("ftp/sftp가 아닌 프로토콜로 업데이트가 불가능합니다.");
            }

            exec('rm -rf ' . self::$dir_version . '/*');

            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 가용용량 체크 (20MB의 여유공간)
     * @return bool
     */
    public function checkInstallAvailable()
    {
        $dfs = disk_free_space("/");

        if (($dfs - 20971520) > 0) {
            return true;
        }

        return false;
    }

    /**
     * 전체 저장공간 출력
     * @return string 용량 + 데이터단위
     */
    public function getTotalStorageSize()
    {
        return $this->getFormatFileSize((int)disk_total_space("/"), 2);
    }

    /**
     * 전체 저장공간의 여유 공간 출력
     * @return string 용량 + 데이터단위
     */
    public function getUseableStorageSize()
    {
        return $this->getFormatFileSize((int)disk_free_space("/"), 2);
    }

    /**
     * 전체 저장공간 중 사용 중인 공간 출력
     * @return string 용량 + 데이터단위
     */
    public function getUseStorageSize()
    {
        $useSpace = disk_total_space("/") - disk_free_space("/");

        return $this->getFormatFileSize((int)$useSpace, 2);
    }
    /**
     * 전체 디스크의 사용률 조회
     * @return float 사용률(소수점 2자리)
     */
    public function getUseStoragePercenty()
    {
        $dts = disk_total_space("/");
        $dff = disk_free_space("/");

        $useSpace = $dts - $dff;

        return round(($useSpace / $dts * 100), 2);
    }

    /**
     * 데이터 단위 추가
     * @brief 입력되는 바이트에 따라 적절한 단위를 자동으로 추가한다.
     * @param string    $bytes        데이터 크기 (byte)       
     * @param int       $decimals     표시할 소수점 자릿수
     * @return string   용량 + 데이터단위
     */
    private function getFormatFileSize (int $bytes, int $decimals = 2) {  
        $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');  
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[$factor];  
    }

    public function setNowVersion($now_version = null)
    {
        $this->now_version = $now_version;
    }

    public function setTargetVersion($target_version = null)
    {
        $this->target_version = $target_version;
    }

    public function setRollbackVersion($backup_dir)
    {
        $backup_version_file = file_get_contents($backup_dir . '/version.php');
        preg_match("/(?<=define\('G5_GNUBOARD_VER', ')(.*?)(?='\);)/", $backup_version_file, $rollback_version); // 백업버전 체크
        $this->rollback_version = "v" . $rollback_version[0];
    }

    public function getRollbackVersion()
    {
        return $this->rollback_version;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     * 버전목록 조회
     * @brief github > releases정보 중에 tag_name만 배열로 만들어 리턴한다.
     * @return array|bool 버전 목록
     */
    public function getVersionList()
    {
        if (empty($this->version_list)) {
            $result = $this->getApiCurlResult('version');
            
            if ($result == false) {
                return false;
            }

            foreach ($result as $key => $var) {
                if (!isset($var->tag_name)) {
                    continue;
                }

                $this->version_list[] = $var->tag_name;
            }
            // latest_version 변수선언 추가 (중복 요청 방지)
            if (isset($this->version_list[0])) {
                $this->latest_version = $this->version_list[0];
            }
        }

        return $this->version_list;
    }

    /**
     * 버전에서 수정된 내용 조회
     * @brief 지정된 태그로 게시된 release를 가져온다.
     * @param string $tag github 태그
     * @return mixed release
     */
    public function getVersionModifyContent($tag = null)
    {
        if ($tag == null) {
            return false;
        }

        $result = $this->getApiCurlResult('modify', $tag);
        if ($result == false) {
            return false;
        }

        return $result->body;
    }

    public function getBackupList($backupPath)
    {
        if (empty($this->backup_list)) {
            if (is_dir($backupPath)) {
                if ($dh = @opendir($backupPath)) {
                    $key = 0;
                    while (($dl = @readdir($dh)) !== false) {
                        if (preg_match('/.zip/i', $dl)) {
                            $backupTime = preg_replace('/.zip/', '', $dl);
                            $listName = date("Y-m-d H:i:s", strtotime($backupTime));
                            $this->backup_list[$key]['realName'] = $dl;
                            $this->backup_list[$key]['listName'] = $listName;
                            $key++;
                        }
                    }
                    closedir($dh);

                    rsort($this->backup_list);
                }
            }
        }
        return $this->backup_list;
    }

    public function getLogTotalCount()
    {
        try {
            if (empty($this->log_list)) {
                if (is_dir(self::$dir_log)) {
                    $dirs = scandir(self::$dir_log);
                    $result = array_values(array_diff($dirs, array('.', '..')));
                    $count = count($result);
                }
                return $count;
            } else {
                throw new Exception("페이지 전체정보를 확인할 수 없습니다.");
            }
        } catch (Exception $e) {
            echo $e->getMessage() . '<br>';
            return false;
        }
    }



    public function getLogList($page = null)
    {
        if (empty($this->log_list)) {
            if (is_dir(self::$dir_log)) {
                if ($dh = @opendir(self::$dir_log)) {
                    while (($dl = readdir($dh)) !== false) {
                        if ($dl == '.' || $dl == '..') {
                            continue;
                        }
                        if (preg_match('/.log/i', $dl)) {
                            list($date, $time, $status, $rand) = explode("_", $dl);
                            $file_name = $dl;

                            switch ($status) {
                                case 'update':
                                    $status_txt = '업데이트';
                                    break;
                                case 'rollback':
                                    $status_txt = '롤백';
                                    break;
                                default:
                                    throw new Exception("상태값이 올바르지 않은 파일입니다.");
                            }

                            $this->log_list[] = array(
                                'filename' => $dl,
                                'datetime' => date('Y-m-d h:i:s', strtotime($date . implode(':', str_split($time, 2)))),
                                'status' => $status_txt
                            );
                        }
                    }
                    closedir($dh);

                    array_multisort(array_map('strtotime', array_column($this->log_list, 'datetime')), SORT_DESC, $this->log_list);

                    return $this->log_list;
                }
            }

            return false;
        } else {
            return $this->log_list;
        }
    }

    public function getLogListSize()
    {
        if ($this->log_page_list == null) {
            return false;
        }

        $count = $this->getLogTotalCount();

        $max_list_size = ceil($count / $this->log_page_list);

        return $max_list_size;
    }

    public function getLogDetail($file_name = null)
    {
        try {
            $file = self::$dir_log . '/' . $file_name;

            if ($file_name == null) {
                throw new Exception("");
            }

            $file_size = filesize($file);
            $file_pointer = fopen($file, 'r');

            if ($file_size <= 0) {
                throw new Exception("빈 파일입니다.");
            }
            if (is_resource($file_pointer) === false) {
                throw new Exception("파일을 열람할 권한이 없습니다.");
            }

            $file_content = fread($file_pointer, $file_size);

            list($date, $time, $status, $rand) = explode("_", $file_name);

            switch ($status) {
                case 'update':
                    $status_txt = '업데이트';
                    break;
                case 'rollback':
                    $status_txt = '롤백';
                    break;
                default:
                    throw new Exception("상태값이 올바르지 않은 파일입니다.");
            }

            $log_detail = array(
                'filename' => $file_name,
                'datetime' => date('Y-m-d h:i:s', strtotime($date . implode(':', str_split($time, 2)))),
                'status' => $status_txt,
                'content' => $file_content,
            );

            return $log_detail;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createBackupZipFile($backupPath)
    {
        try {
            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0707);
            }

            if (!file_exists($backupPath)) {
                $result = exec("zip -r " . $backupPath . " ../../" . " -x '../../data/*'");
            }

            if ($result == false) {
                throw new Exception("백업파일 생성이 실패했습니다.");
            }
            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function unzipBackupFile($backupFile)
    {
        try {
            $backupDir = preg_replace('/.zip/', '', $backupFile);

            if (is_dir($backupDir)) {
                return "suecess";
            }

            if (file_exists($backupFile)) {
                $result = exec("unzip " . $backupFile . " -d " . $backupDir);
            } else {
                throw new Exception("해당 파일이 존재하지 않습니다.");
            }

            if ($result == false) {
                throw new Exception("압축해제에 실패했습니다.");
            }
            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 롤백에 쓰인 파일 삭제
     * @brief 백업 원본인 zip파일은 제외하고 삭제함
     * @todo
     * 1. 백업에 쓰인 파일이 제대로 삭제되지 않는 것 같음..
     * @return void
     */
    public function deleteBackupDir($backupDir)
    {
        $dh = dir($backupDir);
        while (false !== ($dl = $dh->read())) {
            if (($dl != '.') && ($dl != '..')) {
                if (is_dir($backupDir . '/' . $dl)) {
                    $this->deleteBackupDir($backupDir . '/' . $dl);
                } else {
                    @unlink($backupDir . '/' . $dl);
                }
            }
        }
        $dh->close();
        @rmdir($backupDir);
    }

    public function deleteOriginFile($originPath)
    {
        // 롤백 파일 삭제
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }

            if ($this->port == 'ftp') {
                $originPath = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', $originPath);
                $result = ftp_delete($this->conn, $originPath);
            } elseif ($this->port == 'sftp') {
                $result = ssh2_sftp_unlink($this->connPath, $originPath);
            }
            if ($result == false) {
                throw new Exception("파일삭제가 실패하였습니다.");
            }

            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function removeEmptyOriginDir($originDir)
    {
        // 비어있는 dir 삭제
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }

            if (!is_dir($originDir)) {
                throw new Exception("디렉토리가 아닙니다.");
            }

            $dirCheck = $this->checkDirIsEmpty($originDir);
            if ($dirCheck) {
                if ($this->port == 'ftp') {
                    $originDir = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', $originDir);
                    $result = ftp_rmdir($this->conn, $originDir);
                } elseif ($this->port == 'sftp') {
                    $result = ssh2_sftp_rmdir($this->connPath, $originDir);
                }
                if ($result == false) {
                    throw new Exception("디렉토리 삭제가 실패하였습니다.");
                }
            }

            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function checkDirIsEmpty($originDir)
    {
        // dir이 비었는지 체크
        if ($dh = @opendir($originDir)) {
            while (($dl = @readdir($dh)) !== false) {
                if ($dl != "." && $dl != "..") {
                    return false;
                }
            }
            closedir($dh);
        }

        return true;
    }

    public function writeUpdateFile($originPath, $changePath)
    {
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }

            if (!file_exists($changePath)) {
                throw new Exception("업데이트에 존재하지 않는 파일입니다.");
            }
            $fp = fopen($changePath, 'r');
            $content = @fread($fp, filesize($changePath));

            if ($content == false) {
                throw new Exception("파일을 여는데 실패했습니다.");
            }
            if ($this->port == 'ftp') {
                $ftpOriginPath = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', $originPath); // ftp에서는 경로 변경
                $ftpChangePath = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', $changePath); // ftp에서는 경로 변경

                if (ftp_nlist($this->conn, dirname($ftpOriginPath)) == false) {
                    $result = ftp_mkdir($this->conn, dirname($ftpOriginPath));
                    ftp_nb_continue($this->conn); // 디렉토리 생성후 파일을 계속해서 검색/전송
                    if ($result == false) {
                        throw new Exception("ftp를 통한 디렉토리 생성에 실패했습니다.");
                    }
                }

                $fg = fopen($originPath, 'w'); // 덮어쓸 파일 포인터 생성
                $result = ftp_fget($this->conn, $fg, $ftpChangePath, FTP_BINARY);
                if ($result == false) {
                    throw new Exception("ftp를 통한 파일전송에 실패했습니다.");
                }
            } elseif ($this->port == 'sftp') {
                if (!file_exists("ssh2.sftp://" . intval($this->connPath) . $originPath)) {
                    if (!is_dir(dirname($originPath))) {
                        mkdir("ssh2.sftp://" . intval($this->connPath) . dirname($originPath));
                    }

                    $permission = intval(substr(sprintf('%o', fileperms($changePath)), -4), 8);
                    $result = ssh2_scp_send($this->conn, $changePath, $originPath, $permission);
                } else {
                    $result = file_put_contents("ssh2.sftp://" . intval($this->connPath) . $originPath, $content);
                }

                if ($result == false) {
                    throw new Exception("sftp를 통한 파일전송에 실패했습니다.");
                }
            }

            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function writeLogFile($success_list = null, $fail_list = null, $status = null)
    {
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }
            if (empty($success_list) && empty($fail_list)) {
                throw new Exception("기록할 데이터가 존재하지 않습니다.");
            }
            if ($status != "update" && $status != "rollback") {
                throw new Exception("올바르지 않은 명령입니다.");
            }

            if ($this->port == 'ftp') {
                $ftp_log_dir = preg_replace("/(.*?)(?=\\" . ftp_pwd($this->conn) . ")/", '', self::$dir_log);

                if (ftp_nlist($this->conn, dirname($ftp_log_dir)) == false) {
                    $result = ftp_mkdir($this->conn, $ftp_log_dir);
                    if ($result == false) {
                        throw new Exception("디렉토리 생성에 실패했습니다.");
                    }
                }

                $datetime = date('Y-m-d_his', time());
                $rand = rand(0000, 9999);

                $fp = fopen(self::$dir_log . "/" . $datetime . '_' . $status . '_' . $rand . '.log', 'w+');
                if ($fp == false) {
                    throw new Exception('파일생성에 실패했습니다.');
                }

                switch ($status) {
                    case 'update':
                        $success_txt = "성공한 업데이트 내역\n";
                        $fail_txt = "실패한 업데이트 내역\n";
                        break;
                    case 'rollback':
                        $success_txt = "성공한 롤백 내역\n";
                        $fail_txt = "롤백 시 제거된 파일 내역\n";
                        break;
                    default:
                        ftp_delete($this->conn, $ftp_log_dir . "/" . $datetime . '_' . $status . '_' . $rand . '.log');
                        throw new Exception("올바르지 않은 명령입니다.");
                }

                if (count($success_list) > 0) {
                    foreach ($success_list as $key => $var) {
                        $success_txt .= $var . "\n";
                    }
                } else {
                    $success_txt = '';
                }

                if (isset($fail_list)) {
                    if (count($fail_list) > 0) {
                        foreach ($fail_list as $key => $var) {
                            $fail_txt .= $var['file']." : ".$var['message']."\n";
                        }
                    } else {
                        $fail_txt = '';
                    }
                }

                $result = fwrite($fp, $success_txt . "\n\n" . $fail_txt);
                if ($result == false) {
                    throw new Exception('파일쓰기에 실패했습니다.');
                }
            } elseif ($this->port == 'sftp') {
                if (!is_dir(self::$dir_log)) {
                    $result = mkdir("ssh2.sftp://" . intval($this->connPath) . self::$dir_log);
                    if ($result == false) {
                        throw new Exception("디렉토리 생성에 실패했습니다.");
                    }
                }

                $datetime = date('Y-m-d_his', time());
                $rand = rand(0000, 9999);

                $fp = fopen("ssh2.sftp://" . intval($this->connPath) . self::$dir_log . "/" . $datetime . '_' . $status . '_' . $rand . '.log', 'w+');
                if ($fp == false) {
                    throw new Exception('파일생성에 실패했습니다.');
                }

                switch ($status) {
                    case 'update':
                        $success_txt = "성공한 업데이트 내역\n";
                        $fail_txt = "실패한 업데이트 내역\n";
                        break;
                    case 'rollback':
                        $success_txt = "성공한 롤백 내역\n";
                        $fail_txt = "실패한 롤백 내역\n";
                        break;
                    default:
                        unlink("ssh2.sftp://" . intval($this->connPath) . self::$dir_log . "/" . $datetime . '_' . $status . '_' . $rand . '.log');
                        throw new Exception("올바르지 않은 명령입니다.");
                }

                if (count($success_list) > 0) {
                    foreach ($success_list as $key => $var) {
                        $success_txt .= $var . "\n";
                    }
                } else {
                    $success_txt = '';
                }
                if (is_array($fail_list)) {
                    if (count($fail_list) > 0) {
                        foreach ($fail_list as $key => $var) {
                            $fail_txt .= $var['file'] . " : " . $var['message'] . "\n";
                        }
                    } else {
                        $fail_txt = '';
                    }
                }

                $result = fwrite($fp, $success_txt . "\n\n" . $fail_txt);
                if ($result == false) {
                    throw new Exception('파일쓰기에 실패했습니다.');
                }
            }

            return true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";

            return false;
        }
    }

    public function downloadVersion($version = null)
    {
        if ($version == null) {
            return false;
        }
        if ($this->conn == false) {
            return false;
        }

        umask(0002);

        $save = self::$dir_version . "/gnuboard.zip";

        $zip = fopen($save, 'w+');
        if ($zip == false) {
            return false;
        }

        $result = $this->getApiCurlResult('zip', $version);
        if ($result == false) {
            return false;
        }

        $file_result = @fwrite($zip, $result);
        if ($file_result == false) {
            return false;
        }

        exec('unzip ' . $save . ' -d ' . self::$dir_version . '/' . $version);
        exec('mv ' . self::$dir_version . '/' . $version . '/gnuboard-*/* ' . self::$dir_version . '/' . $version);
        exec('rm -rf ' . self::$dir_version . '/' . $version . '/gnuboard-*/');
        exec('rm -rf ' . $save);

        umask(0022);

        return true;
    }

    public function checkSameVersionComparison($list = null)
    {
        if ($this->now_version == null) {
            return false;
        }
        if ($list == null) {
            return false;
        }

        $result = $this->downloadVersion($this->now_version);
        if ($result == false) {
            return false;
        }

        $check = array();
        $check['type'] = 'Y';

        foreach ($list as $key => $var) {
            $now_file_path = G5_PATH . '/' . $var;
            $release_file_path = self::$dir_version . '/' . $this->now_version . '/' . $var;

            if (!file_exists($now_file_path)) {
                continue;
            }
            if (!file_exists($release_file_path)) {
                continue;
            }

            $now_content = preg_replace('/\r\n|\r|\n/', '', file_get_contents($now_file_path, true));
            $release_content = preg_replace('/\r\n|\r|\n/', '', file_get_contents($release_file_path, true));

            if ($now_content !== $release_content) {
                $check['type'] = 'N';
                $check['item'][] = $var;
            }
        }

        return $check;
    }

    public function checkRollbackVersionComparison($list = null, $backupFile)
    {
        if ($this->now_version == null) {
            return false;
        }
        if ($list == null) {
            return false;
        }

        $result = $this->downloadVersion($this->now_version);
        if ($result == false) {
            return false;
        }

        $check = array();
        $check['type'] = 'Y';

        foreach ($list as $key => $var) {
            $now_file_path = G5_PATH . '/' . $var;
            $release_file_path = preg_replace('/.zip/', '', $backupFile);

            if (!file_exists($now_file_path)) {
                continue;
            }
            if (!file_exists($release_file_path)) {
                continue;
            }

            $now_content = preg_replace('/\r/', '', file_get_contents($now_file_path, true));
            $release_content = preg_replace('/\r/', '', file_get_contents($release_file_path, true));

            if ($now_content !== $release_content) {
                $check['type'] = 'N';
                $check['item'][] = $var;
            }
        }

        return $check;
    }
    
    /**
     * 최신버전 조회
     * @brief 버전목록 중 첫번째 인덱스 값을 조회한다.
     * @return string|bool 최신버전
     */
    public function getLatestVersion()
    {
        if ($this->latest_version == null) {
            $result = $this->getVersionList();

            if ($result == false) {
                return false;
            }
            $this->latest_version = $result[0];
        }

        return $this->latest_version;
    }

    public function getVersionCompareList()
    {
        try {
            if ($this->now_version == null || $this->target_version == null) {
                throw new Exception("현재버전 및 목표버전이 설정되지 않았습니다.");
            }
            if ($this->now_version == $this->target_version) {
                throw new Exception("동일버전으로는 업데이트가 불가능합니다.");
            }

            $version_list = $this->getVersionList();
            if ($version_list == false) {
                throw new Exception("버전리스트를 가져오는데 실패했습니다.");
            }

            // 숫자가 작을수록 상위버전
            $now_version_num = array_search($this->now_version, $version_list);
            $target_version_num = array_search($this->target_version, $version_list);

            if ($now_version_num > $target_version_num) {
                $result = $this->getApiCurlResult("compare", $this->now_version, $this->target_version);
            } else {
                $result = $this->getApiCurlResult("compare", $this->target_version, $this->now_version);
            }

            if ($result == false) {
                throw new Exception("비교리스트확인 통신에 실패했습니다.");
            }

            foreach ($result->files as $key => $var) {
                $this->compare_list[] = $var->filename;
            }

            return $this->compare_list;
        } catch (Exception $e) {
            print_r2($e->getMessage());
            return false;
        }
    }

    public function buildFolderStructure(&$dirs, $path_array)
    {
        if (count($path_array) > 1) {
            if (!isset($dirs[$path_array[0]])) {
                $dirs[$path_array[0]] = array();
            }

            $this->buildFolderStructure($dirs[$path_array[0]], array_splice($path_array, 1));
        } else {
            if (!in_array($path_array[0], $dirs)) {
                $dirs[] = $path_array[0];
            }
        }
    }

    public function changeDepthListPrinting($list, $depth = 0)
    {
        if (!is_array($list)) {
            if (strpos($list, "변경") !== false) {
                $list = "<font style=\"color:red; font-weight:bold;\">" . $list . "</font>";
            }
            return $list . "<br>";
        }
        $line = '';
        if ($depth > 0) {
            $line = '&#9492; &nbsp;';
        }

        $txt = '';
        foreach ($list as $key => $var) {
            for ($i = 0; $i < ($depth * 2) - 1; $i++) {
                $txt .= "&nbsp; &nbsp;";
            }
            if ($depth > 0) {
                $txt .= $line;
            }

            if (is_array($var)) {
                $txt .= $key . "<br>";
            }

            $txt .= $this->changeDepthListPrinting($var, $depth + 1);
        }

        return $txt;
    }

    public function getDepthVersionCompareList()
    {
        try {
            $compare_list = $this->getVersionCompareList();
            if ($compare_list == false) {
                throw new Exception("비교리스트 확인에 실패했습니다.");
            }

            $result = $this->checkSameVersionComparison($compare_list);
            if ($result == false) {
                throw new Exception("파일 비교에 실패했습니다.");
            }

            foreach ($compare_list as $key => $var) {
                if (isset($result['item'])) {
                    if (@in_array($var, $result['item'])) {
                        $compare_list[$key] = $var . " (변경)";
                    }
                }
            }

            $parray = array();
            foreach ($compare_list as $key => $var) {
                $path_array = explode('/', $var);
                $this->buildFolderStructure($parray, $path_array);
            }

            return $parray;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getApiCurlResult($option, $param1 = null, $param2 = null)
    {
        if ($this->token != null) {
            $auth = 'Authorization: token ' . $this->token;
        }
        $url = $this->url;
        switch ($option) {
            case "version":
                $url .= "/repos/gnuboard/gnuboard5/releases";
                break;
            case "compare":
                if ($param1 == null || $param2 == null) {
                    return false;
                }
                $url .= "/repos/gnuboard/gnuboard5/compare/" . $param1 . "..." . $param2;
                break;
            case "zip":
                if ($param1 == null) {
                    return false;
                }
                $url .= "/repos/gnuboard/gnuboard5/zipball/" . $param1;
                break;
            case "modify":
                if ($param1 == null) {
                    return false;
                }
                $url .= "/repos/gnuboard/gnuboard5/releases/tags/" . $param1;
                break;
            default:
                $url = false;
                break;
        }

        if ($url == false) {
            return false;
        }

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_USERAGENT => 'gnuboard',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 3600,
                CURLOPT_AUTOREFERER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_FAILONERROR => true,
                CURLOPT_HTTPHEADER => array(
                    $auth
                ),
            )
        );

        $cinfo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($option == 'zip') {
            $response = curl_exec($curl);
        } else {
            $response = json_decode(curl_exec($curl));
        }

        if (curl_errno($curl)) {
            return false;
        }

        return $response;
    }

    public function setError($msg)
    {
        echo $msg;
        exit;
    }

    /**
     * 프로토콜 목록 조회
     * @return array 프로토콜 목록
     */
    public function getProtocolList()
    {   
        $protocol_list = array();
        if (function_exists("ftp_connect")) {
            $protocol_list[] = 'ftp';
        }
        if (function_exists('ssh2_connect')) {
            $protocol_list[] = 'sftp';
        }
        return $protocol_list;
    }
}
