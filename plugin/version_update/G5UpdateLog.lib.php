<?php

/**
 * DB업데이트 로그 Class
 *
 * @see 권한문제로 인해 일반적인 php함수를 사용해서 경로/파일생성을 한다.
 */
class G5UpdateLog
{
    private $g5Update;

    public static $logPath;

    public $logList = array();
    public $totalCount = 0;

    private const PAGE_LIMIT = 10;

    public function __construct()
    {
        $this->g5Update = new G5Update();
        self::$logPath = $this->g5Update->dir_update . "/log";
    }

    /**
     * 로그 디렉토리 생성
     *
     * @return void
     */
    public static function makeDirectory()
    {
        try {
            if (!self::existDirectory()) {
                $result = mkdir(self::$logPath, 0755);
                if (!$result) {
                    throw new Exception("로그파일 경로생성이 실패했습니다. 경로 : " . self::$logPath);
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 로그 디렉토리 존재여부 확인
     *
     * @return bool
     */
    public static function existDirectory()
    {
        if (is_dir(self::$logPath)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 로그파일 목록 조회
     *
     * @param  int $page 현재페이지
     * @return array<mixed>|void
     */
    public function getLogList($page = 1)
    {
        try {
            if (empty($this->logList)) {
                if (!self::existDirectory()) {
                    throw new Exception("존재하지 않는 로그파일 경로입니다. 경로 : " . self::$logPath);
                }
                if (!$resource = opendir(self::$logPath)) {
                    throw new Exception("로그파일 경로를 열 수 없습니다. 경로 : " . self::$logPath);
                }

                while (($fileName = readdir($resource)) !== false) {
                    if ($fileName == '.' || $fileName == '..') {
                        continue;
                    }
                    if (preg_match('/.log/i', $fileName)) {
                        $this->logList[] = $this->getLogFileInfo($fileName);
                    }
                }
                closedir($resource);

                $this->setTotalCount(count($this->logList));

                // 최신순 정렬
                $logListTimestamp = array_map('strtotime', $this->g5Update->arrayColumn($this->logList, 'datetime'));
                array_multisort($logListTimestamp, SORT_DESC, $this->logList);

                // 페이징 처리
                $start = $page ? ((int)$page - 1) * self::PAGE_LIMIT : 0;
                $end = $start + self::PAGE_LIMIT;
                $this->logList = array_slice($this->logList, $start, $end, true);
            }

            return $this->logList;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 로그파일 상세정보 조회
     *
     * @param  string $fileName 로그파일명
     * @return array<mixed> 로그파일 상세정보
     * @throws Exception
     */
    public function getLogDetail($fileName = null)
    {
        try {
            $filePath = self::$logPath . '/' . $fileName;
            if (!file_exists($filePath)) {
                throw new Exception("존재하지 않는 로그파일입니다. 경로 : " . $filePath);
            }

            $logInfo = $this->getLogFileInfo($fileName);

            $fileSize = filesize($filePath);
            $filePointer = fopen($filePath, 'r');
            if ($fileSize <= 0) {
                throw new Exception("빈 로그 파일입니다.");
            }
            if (is_resource($filePointer) === false) {
                throw new Exception("파일을 열람할 권한이 없습니다.");
            }
            $fileContent = fread($filePointer, $fileSize);
            if ($fileContent) {
                $logInfo['content'] = $fileContent;
            }

            return $logInfo;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * 로그파일 정보 추출
     *
     * @param  string $fileName
     * @return array<mixed>
     * @throws Exception
     */
    public function getLogFileInfo($fileName = null)
    {
        list($date, $time, $status, $rand) = explode("_", (string)$fileName);

        if (empty($date) || empty($time)) {
            throw new Exception("올바른 파일명 형식이 아닙니다. 파일명 : " . $fileName);
        }
        switch ($status) {
            case 'update':
                $status_txt = '업데이트';
                break;
            case 'rollback':
                $status_txt = '롤백';
                break;
            default:
                $status_txt = "상태 값이 올바르지 않은 파일입니다.";
                break;
        }
        $time = $date . implode(':', str_split($time, 2));
        return array(
            'filename' => $fileName,
            'datetime' => date('Y-m-d H:i:s', (int)strtotime($time)),
            'status' => $status_txt
        );
    }

    /**
     * 로그파일 목록 페이징 수 계산
     *
     * @return float
     */
    public function getPageCount()
    {
        $page = ceil($this->getTotalCount() / self::PAGE_LIMIT);

        return $page ? $page : 1;
    }

    /**
     * 로그파일 삭제
     *
     * @param  string $fileName
     * @return void
     * @throws Exception
     */
    public function deleteLogFile($fileName = null)
    {
        try {
            $filePath = self::$logPath . '/' . $fileName;
            if (!file_exists($filePath)) {
                throw new Exception("존재하지 않는 로그파일입니다. 경로 : " . $filePath);
            }
            if (!unlink($filePath)) {
                throw new Exception("파일을 삭제하지 못했습니다. 파일 : " . $filePath);
            }
        } catch (Exception $e) {
            echo $e->getCode() . " " . $e->getMessage();
            exit;
        }
    }

    /**
     * 로그파일 생성
     *
     * @param  array<mixed> $updateResult   업데이트/복원 결과
     * @param  string       $status         상태값
     * @return void
     * @throws Exception
     */
    public static function createLogFile($updateResult = null, $status = null)
    {
        try {
            // 로그 디렉토리 생성
            self::makeDirectory();

            if (empty($updateResult['success']) && empty($updateResult['fail'])) {
                throw new Exception("기록할 데이터가 존재하지 않습니다.");
            }
            // 로그파일 내용 추가
            $fileContent = "버전 변경 내역\n";
            $fileContent .= G5Version::$currentVersion . " ▶▶ " .  G5Update::getTargetVersion() . "\n\n";

            $successList = $updateResult['success'];
            $failList = $updateResult['fail'];
            switch ($status) {
                case 'update':
                    $successText = "성공한 업데이트 내역\n";
                    $failText = "실패한 업데이트 내역\n";
                    break;
                case 'rollback':
                    $successText = "성공한 롤백 내역\n";
                    $failText = "롤백 시 제거된 파일 내역\n";
                    break;
                default:
                    throw new Exception("올바르지 않은 명령입니다.");
            }
            if (isset($successList) && is_array($successList)) {
                foreach ($successList as $key => $file) {
                    $successText .= $file . "\n";
                }
            }
            if (isset($failList) && is_array($failList)) {
                foreach ($failList as $key => $fail) {
                    if (is_array($fail)) {
                        $failText .= $fail['file'] . " : " . $fail['message'] . "\n";
                    }
                }
            }
            $fileContent .= $successText . "\n\n" . $failText;

            // 파일명 생성
            $datetime = date('Y-m-d_His', time());
            $rand = sprintf('%04d', rand(0000, 9999));
            $logFileName = $datetime . '_' . $status . '_' . $rand . '.log';

            // 로그파일 생성
            $filePointer = fopen(self::$logPath . "/" . $logFileName, 'w+');
            if ($filePointer == false) {
                throw new Exception('로그파일 생성에 실패했습니다.');
            }
            $result = fwrite($filePointer, $fileContent);
            if ($result == false) {
                throw new Exception('로그파일 쓰기에 실패했습니다.');
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * get totalCount
     *
     * @return int
     */
    public function getTotalCount()
    {
        return (int)$this->totalCount;
    }
    /**
     * set totalCount
     *
     * @param int $count
     * @return void
     */
    public function setTotalCount($count = 0)
    {
        $this->totalCount = $count;
    }
}
