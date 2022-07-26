<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * 그누보드5 버전 업데이트
 * @todo
 * 1. disk_total_space, disk_free_space > 호스팅 환경에서는 용량표시가 제대로 안되는 듯..
 * 2. 업데이트에 필요한 최소용량 및 용량 초과시 발생하는 오류
 *
 */
class G5Update
{
    public $path = null;
    public $latest_version = null;
    public $target_version = null;
    public $rollback_version = null;
    public $now_version = null;

    public $dir_update     = null;
    public $dir_version    = null;
    public $dir_backup     = null;
    public $dir_log        = null;

    public $ftp_dir_update     = null;
    public $ftp_dir_version    = null;
    public $ftp_dir_log        = null;
    public $ftp_dir_backup     = null;

    public $window_dir_update  = null;

    // 가용용량
    const INSTALLED_DISK_CAPITAL = 20971520;

    private $version_list = array();
    private $compare_list = array();
    private $backup_list = array();
    private $log_list = array();

    private $conn;
    private $port;
    private $connPath;

    private $log_page_list = 10;

    // 운영체제 (Linux,WINNT)
    public $os = null;

    public function __construct()
    {
        $this->dir_update   = G5_DATA_PATH . "/update";
        $this->dir_version  = G5_DATA_PATH . "/update/version";
        $this->dir_backup   = G5_DATA_PATH . "/update/backup";
        $this->dir_log      = G5_DATA_PATH . "/update/log";

        $this->os = PHP_OS;

        $this->window_dir_update = str_replace('/', '\\', G5_DATA_PATH) . "\\update";
    }

    /**
     * FTP/SSH 연결
     * @param string $hostname      접속할 host
     * @param string $port          접속 프로토콜 ("ftp", "sftp")
     * @param string $username      사용자 이름
     * @param string $userPassword  사용자 비밀번호
     * @throws Exception
     */
    public function connect($hostname, $port, $username, $userPassword)
    {
        try {
            if ($port == null) {
                throw new Exception("포트가 입력되지 않았습니다.");
            }
            if ($username == null) {
                throw new Exception("{$port} 계정이 입력되지 않았습니다.");
            }
            if ($userPassword == null) {
                throw new Exception("{$port} 비밀번호가 입력되지 않았습니다.");
            }
            $this->port = $port;

            if ($port == "ftp") {
                if (function_exists("ftp_connect")) {
                    $this->conn = @ftp_connect($hostname, 21, 5);
                    if ($this->conn == false) {
                        throw new Exception("FTP 연결에 실패했습니다.");
                    }
                    if (ftp_login($this->conn, $username, $userPassword)) {
                        throw new Exception("FTP 계정 로그인에 실패했습니다.");
                    }
                    // 패시브모드 설정
                    ftp_pasv($this->conn, true);
                    // ftp경로 설정
                    $this->setFtpDirectoryToG5();
                } else {
                    throw new Exception("ftp_connect 함수를 사용할 수 없습니다.");    
                }
            } elseif ($port == "sftp") {
                if (function_exists("ssh2_connect")) {
                    if (!$this->conn) {
                        $this->conn = @ssh2_connect($hostname, 22);
                        if ($this->conn == false) {
                            throw new Exception("SFTP 연결에 실패했습니다.");
                        }
                        if (!ssh2_auth_password($this->conn, $username, $userPassword)) {
                            throw new Exception("SFTP 계정 로그인에 실패했습니다.");
                        }
        
                        $this->connPath = @ssh2_sftp($this->conn);
                        
                        if (!$this->connPath) {
                            $this->conn = false;
                            $this->connPath = false;
                            throw new Exception("SFTP 시스템초기화에 실패했습니다.");
                        }    
                    }
                } else {
                    throw new Exception("ssh2_connect 함수를 사용할 수 없습니다.");    
                }
            } else {
                throw new Exception("올바르지 않은 프로토콜입니다.");
            }
        } catch (Exception $exc) {
            $this->setError($exc);
        }
    }

    /**
     * FTP/SSH 연결해제
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

    /**
     * connect 조회
     * @return mixed
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * ftp connect gnuboard5경로 설정
     * @breif ftp 연결 시 홈 디렉토리가 서버 및 계정 설정에 따라 다 제각각이므로 초기경로를 잡아준다.
     * @return void
     */
    public function setFtpDirectoryToG5()
    {
        $arrayPath  = explode("/", G5_PATH);
        $count      = count($arrayPath);

        for ($i = 0; $i < $count; $i++) {
            $relativePath = "";
            for ($j = 0; $j < count($arrayPath); $j++) {
                $relativePath .= $arrayPath[$j] . "/";
            }
            if (@ftp_chdir($this->conn, $relativePath)) {
                // 현재 경로가 그누보드인지 체크해야함. (data폴더)
                $original_directory = (string)ftp_pwd($this->conn);
                if (@ftp_chdir($this->conn, (string)ftp_pwd($this->conn) . "/data")) {
                    ftp_chdir($this->conn, $original_directory);
                    break;
                }
            } else {
                array_shift($arrayPath);
            }
        }

        if (ftp_pwd($this->conn)) {
            $pwd = (ftp_pwd($this->conn) != "/") ? ftp_pwd($this->conn) : "";

            $this->ftp_dir_update   = $pwd . "/data/update";
            $this->ftp_dir_version  = $this->ftp_dir_update . "/version";
            $this->ftp_dir_log      = $this->ftp_dir_update . "/log";
            $this->ftp_dir_backup   = $this->ftp_dir_update . "/backup";
        }
    }

    /**
     * 버전업데이트 경로 생성 및 권한처리
     * @throws Exception
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
                if (!is_dir($this->dir_update)) {
                    if (!ftp_mkdir($this->conn, $this->ftp_dir_update)) {
                        throw new Exception("/update 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if ($this->os != "WINNT") {
                        if (!ftp_chmod($this->conn, 0707, $this->ftp_dir_update)) {
                            throw new Exception("/update 디렉토리의 권한을 변경하는데 실패했습니다.");
                        }
                    }
                }

                if (!is_dir($this->dir_version)) {
                    if (!ftp_mkdir($this->conn, $this->ftp_dir_version)) {
                        throw new Exception("/update/version 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if ($this->os != "WINNT") {
                        if (!ftp_chmod($this->conn, 0707, $this->ftp_dir_version)) {
                            throw new Exception("/update/version 디렉토리의 권한을 변경하는데 실패했습니다.");
                        }
                    }
                }

                if (!is_dir($this->dir_log)) {
                    if (!ftp_mkdir($this->conn, $this->ftp_dir_log)) {
                        throw new Exception("/update/log 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if ($this->os != "WINNT") {
                        if (!ftp_chmod($this->conn, 0755, $this->ftp_dir_log)) {
                            throw new Exception("/update/log 디렉토리의 권한을 변경하는데 실패했습니다.");
                        }
                    }
                }

                if (!is_dir($this->dir_backup)) {
                    if (!ftp_mkdir($this->conn, $this->ftp_dir_backup)) {
                        throw new Exception("/update/backup 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if ($this->os != "WINNT") {
                        if (!ftp_chmod($this->conn, 0707, $this->ftp_dir_backup)) {
                            throw new Exception("/update/backup 디렉토리의 권한을 변경하는데 실패했습니다.");
                        }
                    }
                }
            } elseif ($this->port == 'sftp') {
                if (!is_dir($this->dir_update)) {
                    if (!ssh2_sftp_mkdir($this->connPath, $this->dir_update, 0707)) {
                        throw new Exception("/update 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, $this->dir_update, 0707)) {
                        throw new Exception("/update 디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir($this->dir_version)) {
                    if (!ssh2_sftp_mkdir($this->connPath, $this->dir_version, 0707)) {
                        throw new Exception("/update/version 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, $this->dir_version, 0707)) {
                        throw new Exception("/update/version 디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir($this->dir_log)) {
                    if (!ssh2_sftp_mkdir($this->connPath, $this->dir_log, 0755)) {
                        throw new Exception("/update/log 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, $this->dir_log, 0755)) {
                        throw new Exception("/update/log 디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }

                if (!is_dir($this->dir_backup)) {
                    if (!ssh2_sftp_mkdir($this->connPath, $this->dir_backup, 0707)) {
                        throw new Exception("/update/backup 디렉토리를 생성하는데 실패했습니다.");
                    }
                    if (!ssh2_sftp_chmod($this->connPath, $this->dir_backup, 0707)) {
                        throw new Exception("/update/backup 디렉토리의 권한을 변경하는데 실패했습니다.");
                    }
                }
            } else {
                throw new Exception("ftp/sftp가 아닌 프로토콜로 업데이트가 불가능합니다.");
            }

            //.htaccess 파일 생성
            if (!file_exists($this->dir_update . "/.htaccess")) {
                $fp = fopen($this->dir_update . "/.htaccess", 'w+');
                if ($fp) {
                    fwrite($fp, "Deny from all");
                    fclose($fp);
                } else {
                    throw new Exception(".htaccess 파일생성에 실패했습니다.");
                }
            }

            // 시스템명령어 구분
            if ($this->os == "WINNT") {
                $dirRemove = escapeshellarg($this->window_dir_update . "\\version\\*.*");
                exec("for /d %i in (" . $dirRemove . ") do @rmdir /s /q \"%i\"");
            } else {
                $dirRemove = escapeshellarg($this->dir_version . '/*');
                exec('rm -rf ' . $dirRemove);
            }

        } catch (Exception $e) {
            $this->setError($e);
        }
    }

    /**
     * 가용용량 체크
     * @throws Exception
     */
    public function checkInstallAvailable()
    {
        try {
            $dfs = disk_free_space("/");
            if ($dfs < self::INSTALLED_DISK_CAPITAL) {
                throw new Exception("설치가능 공간이 부족합니다. (" . $this->getFormatFileSize(self::INSTALLED_DISK_CAPITAL, 0) . " 이상 필요)");
            }
        } catch (Exception $e) {
            $this->setError($e);
        }
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
     * @param int   $bytes        데이터 크기 (byte)
     * @param int   $decimals     표시할 소수점 자릿수
     * @return string   용량 + 데이터단위
     */
    private function getFormatFileSize($bytes, $decimals = 2)
    {
        $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen((string)$bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[$factor];
    }

    /**
     * 버전목록 조회
     * @brief github > releases정보 중에 tag_name만 배열로 만들어 리턴한다.
     * @return array<mixed>|bool 버전 목록
     */
    public function getVersionList()
    {
        if (empty($this->version_list)) {
            $result = G5GithubApi::getVersionData(40);
            
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
        $result = G5GithubApi::getModifyData($tag);
        if ($result == false) {
            return false;
        }

        return $result->body;
    }
    /**
     * 백업파일 목록 조회
     * @breif zip파일만 조회
     * @return array<array<mixed>>
     */
    public function getBackupList()
    {
        if (empty($this->backup_list)) {
            if (is_dir($this->dir_backup)) {
                if ($dh = @opendir($this->dir_backup)) {
                    $key = 0;
                    $exe = ($this->os == "WINNT") ? "tar" : "zip";
                    while (($dl = @readdir($dh)) !== false) {
                        if (preg_match('/.' . $exe . '/i', $dl)) {
                            $fileName = preg_replace('/.' . $exe . '/', '', $dl);
                            $arrayFileName  = explode("_", (string)$fileName);
                            $backupTime     = current($arrayFileName);
                            $backupVersion  = end($arrayFileName);
                            $this->backup_list[$key]['realName']    = $dl;
                            $this->backup_list[$key]['version']     = $backupVersion;
                            $this->backup_list[$key]['time']        = date("Y-m-d H:i:s", (int)strtotime($backupTime));
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

    /**
     * 로그파일 목록 조회
     * @param int   $page 현재 페이지
     * @return array<mixed>|bool
     */
    public function getLogList($page = 1)
    {
        if (empty($this->log_list)) {
            if (is_dir($this->dir_log)) {
                if ($dir_resource = @opendir($this->dir_log)) {
                    while (($file_name = readdir($dir_resource)) !== false) {
                        if ($file_name == '.' || $file_name == '..') {
                            continue;
                        }
                        if (preg_match('/.log/i', $file_name)) {
                            list($date, $time, $status, $rand) = explode("_", $file_name);
                            switch ($status) {
                                case 'update':
                                    $status_txt = '업데이트';
                                    break;
                                case 'rollback':
                                    $status_txt = '롤백';
                                    break;
                                default:
                                    $status_txt = "상태값이 올바르지 않은 파일입니다.";
                                    break;
                            }
                            $time = $date . implode(':', str_split($time, 2));
                            $this->log_list[] = array(
                                'filename' => $file_name,
                                'datetime' => date('Y-m-d H:i:s', (int)strtotime($time)),
                                'status' => $status_txt
                            );
                        }
                    }
                    closedir($dir_resource);

                    $logListTimestamp = array_map('strtotime', $this->arrayColumn($this->log_list, 'datetime'));
                    array_multisort($logListTimestamp, SORT_DESC, $this->log_list);
                    
                    // 페이징 처리
                    $start = $page ? ($page - 1) * $this->log_page_list : 0;
                    $end = $start + $this->log_page_list;
                    $log_list = array_slice($this->log_list, $start, $end, true);

                    return $log_list;
                }
            }

            return false;
        } else {
            return $this->log_list;
        }
    }

    /**
     * 전체 로그파일 갯수 조회
     * @return int|bool
     * @throws Exception
     */
    public function getLogTotalCount()
    {
        try {
            $count = 0;
            if (isset($this->log_list)) {
                if (is_dir($this->dir_log)) {
                    $dirs = scandir($this->dir_log);
                    if ($dirs) {
                        $result = array_values(array_diff($dirs, array('.', '..')));
                        $count = count($result);
                    }
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

    /**
     * 로그파일 목록 페이징 수 계산
     * @return float
     */
    public function getLogListSize()
    {
        $count = $this->getLogTotalCount();

        $max_list_size = ceil($count / $this->log_page_list);

        return $max_list_size;
    }

    /**
     * 로그파일 상세정보 조회
     * @param string    $file_name  로그파일 이름
     * @return array<mixed>|bool 로그파일 상세정보
     * @throws Exception
     */
    public function getLogDetail($file_name = null)
    {
        try {
            $file = $this->dir_log . '/' . $file_name;

            if (!is_dir($this->dir_log)) {
                throw new Exception("로그파일 경로가 존재하지 않습니다.");
            }
            if ($file_name == null) {
                throw new Exception("로그파일이 전달되지 않았습니다.");
            }

            $file_size = filesize($file);
            $file_pointer = fopen($file, 'r');

            if ($file_size <= 0) {
                throw new Exception("빈 로그 파일입니다.");
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
            $time = $date . implode(':', str_split($time, 2));
            $log_detail = array(
                'filename' => $file_name,
                'datetime' => date('Y-m-d H:i:s', (int)strtotime($time)),
                'status' => $status_txt,
                'content' => $file_content,
            );

            return $log_detail;
        } catch (Exception $e) {
            return $this->setError($e);
        }
    }
    /**
     * 백업파일 생성
     * @return string
     */
    public function createBackupZipFile()
    {
        try {
            $fileName   = date('YmdHis', G5_SERVER_TIME) . "_" . $this->now_version;
            $exe        = $this->os == "WINNT" ? "tar" : "zip";
            $backupPath = G5_DATA_PATH . "/update/backup/" . $fileName . "." . $exe;
            $result_code = 0;

            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0707);
            }
            if (!file_exists($backupPath)) {
                if ($this->os == "WINNT") {
                    exec("tar -czf " . escapeshellarg($backupPath) ." -C " . G5_PATH . " --exclude data ./*", $output, $result_code);
                } else {
                    exec("zip -r " . escapeshellarg($backupPath) . " ../../" . " -x '../../data/*'", $output, $result_code);
                }
            }
            if ($result_code != 0) {
                throw new Exception("백업파일 생성이 실패했습니다.");
            }
            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    /**
     * 백업파일 압축해제
     * @param string $backupFile
     * @return bool
     * @throws Exception
     */
    public function unzipBackupFile($backupFile = null)
    {
        try {
            $exe        = $this->os == "WINNT" ? "tar" : "zip";
            $backupPath = $this->dir_backup . "/" . $backupFile;
            $backupDir  = preg_replace('/.' . $exe . '/', '', $backupPath);
            $backupDirName = preg_replace('/.' . $exe . '/', '', $backupFile);
            
            // 덮어쓰지 않음
            // if (is_dir((string)$backupDir)) {
            //     return true;
            // }
            if (file_exists($backupPath)) {
                if ($this->os == "WINNT") {
                    if (!is_dir($backupDir)) {
                        if ($this->port == "ftp") {
                            if (!ftp_mkdir($this->conn, $this->ftp_dir_backup . "/" . $backupDirName)) {
                                throw new Exception("/update/backup/" . $backupDirName . " 디렉토리를 생성하는데 실패했습니다.");
                            }
                        } elseif ($this->port == "sftp") {
                            if (!ssh2_sftp_mkdir($this->connPath, $backupDir, 0707)) {
                                throw new Exception("/update/backup/" . $backupDirName . " 디렉토리를 생성하는데 실패했습니다.");
                            }
                        }
                    }
                    exec("tar -zxf " . escapeshellarg($backupPath) . " -C " . escapeshellarg($backupDir), $output, $result_code);
                    if ($result_code != 0) {
                        throw new Exception("압축해제에 실패했습니다.");
                    }
                } else {
                    $result = exec("unzip -o " . escapeshellarg($backupPath) . " -d " . escapeshellarg($backupDir));
                    if (!$result) {
                        throw new Exception("압축해제에 실패했습니다.");
                    }
                }
            } else {
                throw new Exception("백업파일이 존재하지 않습니다.");
            }
            return true;
        } catch (Exception $e) {
            $this->setError($e);
        }
    }

    /**
     * 롤백에 쓰인 파일 삭제
     * @brief 백업 원본인 zip파일은 제외하고 삭제함
     * @param string $backupDir
     * @return void
     */
    public function deleteBackupDir($backupDir)
    {
        $dh = dir($backupDir);
        if ($dh) {
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
    }

    /**
     * 롤백 파일 삭제
     * @param string $originPath
     * @return string
     * @throws Exception
     */
    public function deleteOriginFile($originPath)
    {
        try {
            $result = false;

            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }
            if ($this->port == 'ftp') {
                $ftpOriginPath = "";
                if (ftp_pwd($this->conn) != "/") {
                    $ftpPwd         = str_replace("/", "\/", (string)ftp_pwd($this->conn));
                    $ftpOriginPath  = preg_replace("/(.*?)(?=" . (string)$ftpPwd . ")/", '', $originPath);
                } else {
                    $ftpOriginPath  = str_replace(G5_PATH, '', $originPath);
                }
                $result = ftp_delete($this->conn, (string)$ftpOriginPath);
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

    /**
     * 비어있는 경로 삭제
     * @param string $originDir 경로
     * @return string
     * @throws Exception
     */
    public function removeEmptyOriginDir($originDir)
    {
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }
            if (!is_dir($originDir)) {
                throw new Exception("디렉토리가 아닙니다.");
            }

            $dirCheck = $this->checkDirIsEmpty($originDir);
            if ($dirCheck) {
                $result = false;
                if ($this->port == 'ftp') {
                    $ftpOriginDir = "";
                    if (ftp_pwd($this->conn) != "/") {
                        $ftpPwd         = str_replace("/", "\/", (string)ftp_pwd($this->conn));
                        $ftpOriginDir   = preg_replace("/(.*?)(?=" . (string)$ftpPwd . ")/", '', $originDir);
                    } else {
                        $ftpOriginDir   = str_replace(G5_PATH, '', $originDir);
                    }
                    $result = ftp_rmdir($this->conn, (string)$ftpOriginDir);
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

    /**
     * 경로가 비었는지 체크
     * @param string    $originDir 경로
     * @return bool
     */
    public function checkDirIsEmpty($originDir = "")
    {
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

    /**
     * 업데이트버전 파일 적용
     * @breif downloadVersion에서 만들어놓은 임시경로(/update/version)에서 실제 경로로 적용시킨다
     * @param string $originPath   원본 파일 경로
     * @param string $changePath   업데이트 파일 경로
     * @return string|void
     * @throws Exception
     */
    public function writeUpdateFile($originPath, $changePath)
    {
        try {
            if ($this->conn == false) {
                throw new Exception("통신이 연결되지 않았습니다.");
            }
            if (!file_exists($changePath)) {
                throw new Exception("업데이트에 삭제되거나 존재하지 않는 파일입니다.");
            }

            $ftpChangePath  = "";
            $ftpOriginPath  = "";
            if ($this->port == 'ftp') {
                if (ftp_pwd($this->conn) != "/") {
                    $ftpPwd         = str_replace("/", "\/", (string)ftp_pwd($this->conn));
                    $ftpOriginPath  = preg_replace("/(.*?)(?=" . (string)$ftpPwd . ")/", '', $originPath);
                    $ftpChangePath  = preg_replace("/(.*?)(?=" . (string)$ftpPwd . ")/", '', $changePath);
                } else {
                    $ftpOriginPath  = str_replace(G5_PATH, '', $originPath);
                    $ftpChangePath  = str_replace(G5_PATH, '', $changePath);
                }
            }

            $changeFileSize = filesize($changePath);
            if ($changeFileSize <= 0) {
                throw new Exception("빈파일 입니다.");
            }
            $changeFile = fopen($changePath, 'r');
            if ($changeFile == false) {
                throw new Exception("파일을 여는데 실패했습니다.");
            }
            $changeContent = @fread($changeFile, $changeFileSize);
            if ($changeContent == false) {
                throw new Exception("파일을 읽어들이는데 실패했습니다.");
            }

            if ($this->port == 'ftp') {
                if (ftp_nlist($this->conn, dirname((string)$ftpOriginPath)) == false) {
                    $result = ftp_mkdir($this->conn, dirname((string)$ftpOriginPath));
                    ftp_nb_continue($this->conn); // 디렉토리 생성후 파일을 계속해서 검색/전송
                    if ($result == false) {
                        throw new Exception("FTP를 통한 디렉토리 생성에 실패했습니다.");
                    }
                }
                $restoreContent = "";
                if (file_exists($originPath)) {
                    $restoreFile = @fopen($originPath, 'r+');
                    if ($restoreFile) {
                        $restoreContent = @fread($restoreFile, filesize($originPath));
                    }
                }

                $originFile = fopen($originPath, 'w'); // 덮어쓸 파일 포인터 생성
                if ($originFile == false) {
                    throw new Exception("FTP를 통한 파일전송에 실패했습니다1.");
                }
                $result = ftp_fget($this->conn, $originFile, $ftpChangePath, FTP_BINARY);
                if ($result == false) {
                    if ($restoreContent) {
                        @fwrite($originFile, (string)$restoreContent);
                    }
                    throw new Exception("FTP를 통한 파일전송에 실패했습니다2.");
                }
            } elseif ($this->port == 'sftp') {
                if (!file_exists("ssh2.sftp://" . intval($this->connPath) . $originPath)) {
                    if (!is_dir(dirname($originPath))) {
                        mkdir("ssh2.sftp://" . intval($this->connPath) . dirname($originPath));
                    }

                    $permission = intval(substr(sprintf('%o', fileperms($changePath)), -4), 8);
                    $result = ssh2_scp_send($this->conn, $changePath, $originPath, $permission);
                } else {
                    $result = file_put_contents("ssh2.sftp://" . intval($this->connPath) . $originPath, $changeContent);
                }

                if ($result == false) {
                    throw new Exception("SFTP를 통한 파일전송에 실패했습니다.");
                }
            }

            return "success";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 로그파일 생성
     * @param array<string> $success_list 업데이트/복원 성공목록
     * @param array<array<string, string>> $fail_list 업데이트/복원 실패목록
     * @param string $status 상태값
     * @return bool
     */
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
                if (ftp_nlist($this->conn, dirname($this->ftp_dir_log)) == false) {
                    $result = ftp_mkdir($this->conn, $this->ftp_dir_log);
                    if ($result == false) {
                        throw new Exception("디렉토리 생성에 실패했습니다.");
                    }
                }

                $datetime = date('Y-m-d_His', time());
                $rand = sprintf('%04d', rand(0000, 9999));
                $logFileName = $datetime . '_' . $status . '_' . $rand . '.log';
                $fp = fopen($this->dir_log . "/" . $logFileName, 'w+');
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
                        ftp_delete($this->conn, $this->ftp_dir_log . "/" . $logFileName);
                        throw new Exception("올바르지 않은 명령입니다.");
                }
                if (isset($success_list)) {
                    if (count($success_list) > 0) {
                        foreach ($success_list as $key => $var) {
                            $success_txt .= $var . "\n";
                        }
                    } else {
                        $success_txt = '';
                    }
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
                if (!is_dir($this->dir_log)) {
                    $result = mkdir("ssh2.sftp://" . intval($this->connPath) . $this->dir_log);
                    if ($result == false) {
                        throw new Exception("디렉토리 생성에 실패했습니다.");
                    }
                }

                $datetime = date('Y-m-d_His', time());
                $rand = sprintf('%04d', rand(0000, 9999));
                $logFileName = $datetime . '_' . $status . '_' . $rand . '.log';
                $fp = fopen("ssh2.sftp://" . intval($this->connPath) . $this->dir_log . "/" . $logFileName, 'w+');
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
                        unlink("ssh2.sftp://" . intval($this->connPath) . $this->dir_log . "/" . $logFileName);
                        throw new Exception("올바르지 않은 명령입니다.");
                }
                if (is_array($success_list)) {
                    if (count($success_list) > 0) {
                        foreach ($success_list as $key => $var) {
                            $success_txt .= $var . "\n";
                        }
                    } else {
                        $success_txt = '';
                    }
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

    /**
     * 업데이트버전 파일 다운로드
     * @breif 해당 버전에 맞는 압축파일 다운로드 후, 디렉토리 생성
     * @param string $version 다운로드 버전
     * @return void
     * @throws Exception
     */
    public function downloadVersion($version = null)
    {
        if ($version == null) {
            throw new Exception('다운로드 버전이 설정되지 않았습니다.');
        }
        if ($this->conn == false) {
            throw new Exception("통신이 연결되지 않았습니다.");
        }

        umask(0002);

        $exe            = $this->os == "WINNT" ? "tar" : "zip";
        $archiveFile    = $this->dir_version . "/gnuboard." . $exe;
        
        $zip = fopen($archiveFile, 'w+');
        if ($zip == false) {
            throw new Exception('압축파일 생성에 실패했습니다.');
        }

        $result = G5GithubApi::getArchiveData($exe, $version);
        // $this->getApiCurlResult($exe, $version);
        if ($result == false) {
            throw new Exception($version . '버전 다운로드 통신이 실패했습니다.');
        }
        $file_result = @fwrite($zip, (string)$result);
        if ($file_result == false) {
            throw new Exception('압축파일 생성에 실패했습니다.');
        }

        // 시스템명령어 구분
        if ($this->os == "WINNT") {
            $window_dir_version = $this->window_dir_update . "\\version";
            $versionDir         = $window_dir_version . '\\' . $version;
            $escapeVersionDir   = escapeshellarg($versionDir);

            exec("rd /s /q " . $escapeVersionDir);
            exec("tar -zxf " . escapeshellarg($window_dir_version . "\\gnuboard." . $exe) . " -C " . escapeshellarg($window_dir_version), $output, $result_code);
            if ($result_code != 0) {
                throw new Exception("압축해제에 실패했습니다.");
            }
            exec("move " . escapeshellarg($window_dir_version . "\\gnuboard-*") . " " . $escapeVersionDir, $output, $result_code);
            if ($result_code != 0) {
                throw new Exception("압축파일 이동에 실패했습니다.");
            }
            exec('del /q ' .escapeshellarg($window_dir_version . "\\gnuboard." . $exe));

        } else {
            $versionDir         = $this->dir_version . '/' . $version;
            $escapeVersionDir   = escapeshellarg($versionDir);

            exec('rm -rf ' . $escapeVersionDir);
            $result = exec('unzip ' . escapeshellarg($archiveFile) . ' -d ' . $escapeVersionDir);
            if (!$result) {
                throw new Exception("압축해제에 실패했습니다.");
            }
            $result = exec('mv -f ' . escapeshellarg($versionDir . '/gnuboard-') . '*/* ' . $escapeVersionDir);
            if (!$result) {
                throw new Exception("압축파일 이동에 실패했습니다.");
            }
            exec('rm -rf ' . escapeshellarg($versionDir . '/gnuboard-*/'));
            exec('rm -rf ' . escapeshellarg($archiveFile));
        }   

        umask(0022);
    }

    /**
     * 변경된 파일목록 조회(release)
     * @breif 현재 그누보드와 github의 같은 버전의 파일을 비교
     * @param array<string> $list 동일버전 업데이트 파일목록
     * @return array<mixed>|bool
     * @throws Exception
     */
    public function checkSameVersionComparison($list = null)
    {
        if ($this->now_version == null) {
            throw new Exception('현재 버전이 설정되지 않았습니다.');
        }
        if ($list == null) {
            throw new Exception('업데이트 파일목록이 전달되지 않았습니다.');
        }
        
        $this->downloadVersion($this->now_version);

        $check = array();
        $check['type'] = 'Y';

        foreach ($list as $key => $var) {
            $now_file_path = G5_PATH . '/' . $var;
            $release_file_path = $this->dir_version . '/' . $this->now_version . '/' . $var;

            if (!file_exists($now_file_path)) {
                continue;
            }
            if (!file_exists($release_file_path)) {
                continue;
            }

            $now_content = preg_replace('/\r\n|\r|\n/', '', (string)file_get_contents($now_file_path, true));
            $release_content = preg_replace('/\r\n|\r|\n/', '', (string)file_get_contents($release_file_path, true));

            if ($now_content !== $release_content) {
                $check['type'] = 'N';
                $check['item'][] = $var;
            }
        }

        return $check;
    }

    /**
     * 변경된 파일목록 조회(backup)
     * @breif 현재 그누보드와 백업파일을 비교
     * @param array<string> $list 동일버전 업데이트 파일목록
     * @param string $backupFile
     * @return array<mixed>|bool
     */
    public function checkRollbackVersionComparison($list, $backupFile)
    {
        $exe = $this->os == "WINNT" ? "tar" : "zip";
        $backupPath = preg_replace('/.' . $exe . '/', '', $this->dir_backup . "/" . $backupFile);

        if ($this->now_version == null) {
            throw new Exception('현재 버전이 설정되지 않았습니다.');
        }
        if ($list == null) {
            throw new Exception('비교파일 목록이 없습니다.');
        }
        if (!file_exists((string)$backupPath)) {
            throw new Exception('백업파일이 존재하지 않습니다.');
        }

        $this->downloadVersion($this->now_version);

        $check = array();
        $check['type'] = 'Y';

        // 변경내역 확인
        foreach ($list as $key => $var) {
            $currentFilePath    = G5_PATH . '/' . $var;
            $backupFilePath     = $backupPath . '/' . $var;

            if (!file_exists($currentFilePath)) {
                continue;
            }
            if (!file_exists($backupFilePath)) {
                continue;
            }

            $now_content = preg_replace('/\r/', '', (string)file_get_contents($currentFilePath, true));
            $backupContent = preg_replace('/\r/', '', (string)file_get_contents((string)$backupFilePath, true));

            if ($now_content !== $backupContent) {
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

            foreach ((array)$result as $key => $val) {
                if ($key == 0) {
                    $this->latest_version = $val;
                    break;
                }
            }
        }

        return $this->latest_version;
    }

    /**
     * 비교파일 목록 조회
     * @breif 현재버전과 목표버전 사이에서 변경된 파일목록 조회
     * @todo 동일버전업데이트 : 파일이 변경되어서 동일버전으로 복구하려고 하는 케이스도 있기때문에 허용해야함.
     * @return array<string>|bool
     */
    public function getVersionCompareList()
    {
        try {
            if ($this->now_version == null || $this->target_version == null) {
                throw new Exception("현재버전 또는 목표버전이 설정되지 않았습니다.");
            }
            if ($this->now_version == $this->target_version) {
                throw new Exception("동일버전으로는 업데이트가 불가능합니다.");
            }
            $version_list = $this->getVersionList();
            if ($version_list == false) {
                throw new Exception("버전목록 조회가 실패했습니다.");
            }

            // 숫자가 작을수록 상위버전
            $now_version_num = array_search($this->now_version, (array)$version_list);
            $target_version_num = array_search($this->target_version, (array)$version_list);

            if ($now_version_num > $target_version_num) {
                $result = G5GithubApi::getCompareData($this->now_version, $this->target_version);
                // $result = $this->getApiCurlResult("compare", $this->now_version, $this->target_version);
            } else {
                $result = G5GithubApi::getCompareData($this->target_version, $this->now_version);
                // $result = $this->getApiCurlResult("compare", $this->target_version, $this->now_version);
            }

            if ($result == false) {
                throw new Exception("비교리스트확인 통신에 실패했습니다.");
            }
            
            foreach ($result->files as $var) {
                $this->compare_list[] = $var->filename;
            }

            return $this->compare_list;
        } catch (Exception $e) {
            $this->setError($e);
            return false;
        }
    }

    /**
     * 디렉토리 => 배열형태로 변경
     * @param array<mixed> &$dirs
     * @param array<mixed> $path_array
     * @return void
     */
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

    /**
     * 변경내역 html태그 추가
     * @param array<mixed> $list
     * @param int $depth 하위단계
     * @return string
     */
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
    /**
     * 업데이트 파일목록 데이터 처리
     * @param array $compare_list
     * @param array $compare_cehck
     * @return array<mixed>|bool
     * @todo 삭제되는 파일들을 어떻게 표시할 것인지?
     */
    public function getDepthVersionCompareList($compare_list, $compare_check)
    {
        try {
            foreach ($compare_list as $key => $var) {
                // 원본버전과 변경된 파일
                if (isset($compare_check['item'])) {
                    if (@in_array($var, $compare_check['item'])) {
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
            $this->setError($e);
            return false;
        }
    }

    /**
     * 에러 출력
     * @param Exception $exc
     * @return void
     */
    public function setError($exc)
    {
        echo "Error : " . $exc->getMessage();
        // . "<br>" . $exc->getTraceAsString()
        exit;
    }

    /**
     * 프로토콜 목록 조회
     * @return array<string> 프로토콜 목록
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

    /**
     * htmlspecialchars_decode의 flag를 버전별로 처리
     * @breif ENT_HTML5 flag는 PHP5.4.0 부터 추가됨.
     * @param string $content
     * @return string $html
     */
    public function setHtmlspecialcharsDecode($content)
    {
        $html = "";
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $html = htmlspecialchars_decode($content, ENT_HTML5);
        } else {
            $html = htmlspecialchars_decode($content, ENT_QUOTES);
        }

        return $html;
    }

    /**
     * PHP Array function array_column 구현
     * @breif array_column은 PHP5.5.0부터 지원
     * @param array<array<mixed>> $input
     * @param int|string|null $columnKey
     * @param int|string|null $indexKey
     * @return array
     */
    public function arrayColumn($input, $columnKey = null, $indexKey = null)
    {
        if (! function_exists('array_column')) {
            $array = array();
            if (isset($columnKey)) {
                foreach ($input as $value) {
                    if (!array_key_exists($columnKey, $value)) {
                        return array();
                    }
                    if (is_null($indexKey)) {
                        $array[] = $value[$columnKey];
                    } else {
                        if (!array_key_exists($indexKey, $value)) {
                            $array[] = $value[$columnKey];
                        }
                        $array[$value[$indexKey]] = $value[$columnKey];
                    }
                }
            } else {
                $array = $input;
            }
            return $array;
        } else {
            return array_column($input, $columnKey);
        }
    }

    public function getNowVersion()
    {
        return $this->now_version;
    }

    public function setNowVersion($now_version = null)
    {
        $this->now_version = $now_version;
    }
    
    public function getTargetVersion()
    {
        return $this->target_version;
    }

    public function setTargetVersion($target_version = null)
    {
        $this->target_version = $target_version;
    }

    public function getRollbackVersion()
    {
        return $this->rollback_version;
    }

    /**
     * 롤백버전 조회
     * @breif 백업경로에서 version.php 파일 내의 버전 값만 추출
     * @param string $backupFile 백업파일 경로
     * @return string $rollback_version
     * @throws Exception
     */
    public function setRollbackVersion($backupFile)
    {
        try {
            $exe = $this->os == "WINNT" ? "tar" : "zip";
            $backupDir = preg_replace('/.' . $exe . '/', '', $this->dir_backup . "/" . $backupFile);
            $backupVersionFile = file_get_contents($backupDir . '/version.php');
            preg_match("/(?<=define\('G5_GNUBOARD_VER', ')(.*?)(?='\);)/", (string)$backupVersionFile, $rollback_version); // 백업버전 체크
    
            if (!$rollback_version[0]) {
                throw new Exception("롤백파일의 버전정보를 불러올 수 없습니다.");
            } 
            $this->rollback_version = "v" . $rollback_version[0];
    
            return $this->rollback_version;
            
        } catch (Exception $e) {
            $this->setError($e);
        }
    }
}
