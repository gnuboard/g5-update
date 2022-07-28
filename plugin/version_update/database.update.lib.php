<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
/**
 * 그누보드5 데이터베이스 업데이트 Class
 * - 파일명 규칙 : {버전명}__{설명}.sql
 * 
 * @todo
 * 0. class 함수 & 코드정리 (phpstan / phpcs)
 * 1. 다운그레이드 기능 구현방법 결정
 *  - 폴더 분리
 *      - up / down 폴더
 *      - 파일 이름에 구분자 추가 (flayway)
 *  - 파일 내부에 구분자 추가
 *      - 주석 + SQL
 *      - migration function (laravel / ci4)
 * 2. 수동 DB업데이트 기능
 * 3. 자동 DB업데이트 기능 (FTP으로 버전 업데이트 시)
 *  - 반복적인 체크로 인해 부하가 걸리지 않도록
 * 4. 트랜잭션 처리
 * 5. 보안관련 체크
 * 6. 예외처리
 */
class G5Migration
{
    protected static $mysqli;
    protected static $targetVersion;
    protected $scriptList = array();

    const AUTO_UPDATE               = true;
    public const MIGRATION_TABLE    = G5_TABLE_PREFIX . "migrations";
    public const MIGRATION_PATH     = G5_PLUGIN_PATH . "/version_update";
    public const SCRIPT_PATH        = self::MIGRATION_PATH . "/migration";


    public function __construct()
    {
        try {
            // mysqli connect
            self::$mysqli = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
            if (self::$mysqli->connect_error) {
                throw new Exception('데이터베이스 연결에 실패했습니다. Error Message : ' . self::$mysqli->connect_error);
            }
            // create migration table
            $this->initialSetup();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * migration 초기설정
     *
     * @return void
     */
    private function initialSetup()
    {
        $setup = new G5MigrationSetup();
        if (!$setup->checkExistMigrationTable()) {
            $setup->createMigrationTable();
        }
    }

    /**
     * SQL 스크립트 파일 실행
     *
     * @param  string $scriptFilePath 스크립트 파일 경로
     * @return array<string,string>
     * @throws Exception
     */
    public function executeSqlScriptFile($scriptFilePath = null)
    {
        try {
            $query = "";
            $scriptFlieContent = file((string)$scriptFilePath);
            if (empty($scriptFilePath) || !file_exists($scriptFilePath)) {
                throw new Exception("잘못된 스크립트 파일 경로입니다.\n경로 : " . $scriptFilePath);
            }
            if (!$scriptFlieContent) {
                throw new Exception("스크립트 파일을 읽을 수 없습니다.");
            }

            $query = $this->setQueryFromScript($scriptFlieContent);

            $result = self::$mysqli->multi_query($query);

            while (mysqli_more_results(self::$mysqli)) {
                mysqli_next_result(self::$mysqli);
            }

            if (!$result) {
                throw new Exception("query 실행이 실패했습니다.\n[" . self::$mysqli->errno . "] " . self::$mysqli->error);
            }

            return array("result" => "success", "message" => "success");
        } catch (Exception $exception) {
            echo $exception->getMessage();
            return array("result" => "fail", "message" => $exception->getMessage());
        }
    }

    /**
     * sql 파일 Query문을 사용자 설정에 맞게 변환
     *
     * @param  mixed $scriptFlieContent 
     * @return string
     */
    public static function setQueryFromScript($scriptFlieContent)
    {
        $query = "";

        $query = implode("\n", $scriptFlieContent);
        $query = preg_replace('/^--.*$/m', "", $query);
        $query = preg_replace('/`g5_([^`]+`)/', '`' . G5_TABLE_PREFIX . '$1', (string)$query);

        if (in_array(strtolower(G5_DB_ENGINE), array('innodb', 'myisam'))) {
            $query = preg_replace('/ENGINE=MyISAM/', 'ENGINE=' . G5_DB_ENGINE, (string)$query);
        } else {
            $query = preg_replace('/ENGINE=MyISAM/', '', (string)$query);
        }
        if (G5_DB_CHARSET !== 'utf8') {
            $query = preg_replace('/CHARSET=utf8/', 'CHARACTER SET ' . get_db_charset(G5_DB_CHARSET), (string)$query);
        }

        return (string)$query;
    }

    /**
     * 업데이트 실행
     *
     * @return void
     */
    public function update()
    {
        try {
            // 실행 스크립트 배열
            $excuteList = array();
            // 버전 실행목록 (현재 버전까지 필터링)
            $versionClass = new G5Version();
            $versionList = $versionClass->getExecuteVersionList($this->getTargetVersion());
            // 마이그레이션 파일 리스트 조회
            $scriptList = $this->getMigrationScriptList();

            foreach ($scriptList as $script) {
                $scriptInfo = $this->getMigrationInfoByScriptfileName($script);
                // 버전목록에 미포함 필터링
                if (!in_array($scriptInfo['version'], $versionList)) {
                    continue;
                }
                /*
                 * DB 성공항목 필터링
                 * 22.07.28 주석
                $statement = self::$mysqli->prepare("SELECT mi_id FROM g5_migrations WHERE mi_version = ? AND mi_result = 'success'");
                if ($statement) {
                    $statement->bind_param("s", $scriptInfo['version']);
                    $statement->execute();
                    $statement->store_result();
                    if ($statement->num_rows() > 0) {
                        continue;
                    }
                }
                */
                $excuteList[] = $scriptInfo;
            }
            usort($excuteList, array("self", "sortByVersion"));
            // 스크립트 실행
            $beforeVersion = "";
            $sort = 1;
            foreach ($excuteList as $script) {
                if ($beforeVersion == $script['version']) {
                    $sort++;
                } else {
                    $beforeVersion = $script['version'];
                    $sort = 1;
                }
                $result = $this->executeSqlScriptFile(self::SCRIPT_PATH . "/" . $script['fileName']);
                $result['sort'] = $sort;

                $this->insertMigrationLog($script, $result);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 마이그레이션 결과 저장
     *
     * @param  array<mixed>              $script
     * @param  array<string, int|string> $result
     * @return void
     */
    public function insertMigrationLog($script, $result)
    {
        $sql = "INSERT INTO 
                    g5_migrations 
                SET 
                    mi_version = ?,
                    mi_script = ?,
                    mi_result = ?,
                    mi_sort = ?,
                    mi_execution_date = NOW()";
        $insertLog = self::$mysqli->prepare($sql);
        $insertLog->bind_param("ssss", $script['version'], $script['fileName'], $result['result'], $result['sort']);
        $insertLog->execute();
    }

    /**
     * 버전기준 정렬
     *
     * @param  array<mixed> $a
     * @param  array<mixed> $b
     * @return int|bool
     */
    public function sortByVersion($a, $b)
    {
        if ($this->targetVersion && version_compare(G5Version::$currentVersion, $this->targetVersion, "<")) {
            return version_compare($a["version"], $b["version"]);
        } else {
            return version_compare($b["version"], $a["version"]);
        }
    }

    /**
     * sql 스크립트 파일 목록 조회
     *
     * @return array<mixed>
     */
    public function getMigrationScriptList()
    {
        if (empty($this->scriptList)) {
            if (is_dir(self::SCRIPT_PATH)) {
                if ($dirResource = @opendir(self::SCRIPT_PATH)) {
                    while (($fileName = readdir($dirResource)) !== false) {
                        if ($fileName == '.' || $fileName == '..') {
                            continue;
                        }
                        if ($this->isMigrationFile($fileName)) {
                            $this->scriptList[] = $fileName;
                        }
                    }
                    closedir($dirResource);
                }
            }
        }

        if (!$this->scriptList) {
            throw new Exception("migration 파일을 조회할 수 없습니다.");
        }

        return $this->scriptList;
    }

    /**
     * 파일명 => 마이그레이션 정보 추출
     *
     * @param  string $fileName
     * @return array<mixed>
     */
    public function getMigrationInfoByScriptfileName($fileName = null)
    {
        if (empty($fileName)) {
            throw new Exception("not exist fileName");
        }
        if (!$this->isMigrationFile($fileName)) {
            throw new Exception("유효한 파일 형식이 아닙니다.");
        }

        list($version, $summary) = explode("__", str_replace('.sql', '', $fileName));

        return array("fileName" => $fileName, "version" => $version, "summary" => $summary);
    }

    /**
     * 마이그레이션 파일인지 체크
     *
     * @param  string $fileName
     * @return int|false
     */
    public function isMigrationFile($fileName)
    {
        return preg_match('/^v[a-z0-9-\.]+_{2}[\w]+\.sql$/i', $fileName);
    }
    /**
     * @return string
     */
    public static function getTargetVersion()
    {
        return self::$targetVersion;
    }
    /**
     * @param  string $version
     * @return void
     */
    public static function setTargetVersion($version)
    {
        self::$targetVersion = $version;
    }
}
