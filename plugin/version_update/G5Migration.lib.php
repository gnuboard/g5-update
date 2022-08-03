<?php
/**
 * 그누보드5 데이터베이스 업데이트 Class
 * - 파일명 규칙 : {버전명}__{설명}.sql
 *
 * @todo
 * 0. class 함수 & 코드정리 (phpstan / phpcs)
 * 5. 보안관련 체크
 * 6. 예외처리
 */
class G5Migration
{
    protected static $mysqli;
    protected static $targetVersion;
    protected $migrationList = array();
    protected $migrationMethod;

    const AUTO_UPDATE               = true;
    public const MIGRATION_TABLE    = G5_TABLE_PREFIX . "migrations";
    public const UPDATE_PATH        = G5_PLUGIN_PATH . "/version_update";
    public const MIGRATION_PATH     = self::UPDATE_PATH . "/migration";
    private const FILE_EXE          = "php";

    public function __construct()
    {
        try {
            // mysqli connect
            self::$mysqli = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
            if (self::$mysqli->connect_error) {
                throw new Exception('데이터베이스 연결에 실패했습니다. Error Message : ' . self::$mysqli->connect_error);
            }
            self::$mysqli->set_charset("utf8");

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
     * @param  string $method           마이그레이션 실행함수 ("up" or "down")
     * @param  string $fileName         migration 파일명
     * @return array<string,string>
     * @throws Exception
     */
    public function executeSqlScriptFile($method = null, $fileName = null)
    {
        try {
            $ignoreMethod = array("up", "down");
            $filePath = self::MIGRATION_PATH . "/" . $fileName;

            if (empty($fileName) || !file_exists($filePath)) {
                throw new Exception("잘못된 migration 파일입니다.");
            }
            if (!isset($method) || !in_array($method, $ignoreMethod)) {
                throw new Exception("올바르지 않은 함수 값입니다.");
            }

            $class = $this->initMigrationClassWithFilename($fileName);
            $class->$method();

            return array("result" => "success", "message" => "");
        } catch (Error $error) {
            return array("result" => "fail", "message" => $error->getMessage());
        } catch (Exception $exception) {
            return array("result" => "fail", "message" => $exception->getMessage());
        }
    }

    /**
     * Migration File의 Class 선언
     * @param  string $fileName
     * @return object
     */
    public function initMigrationClassWithFilename($fileName)
    {
        include_once self::MIGRATION_PATH . "/" . $fileName;

        $replace_array = array("." . self::FILE_EXE, ".", "-");
        $className = str_replace($replace_array, "", $fileName);
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

        return new $className;
    }

    /**
     * .sql파일의 Query문을 사용자 설정으로 변환
     *
     * @param  array<string> $scriptFlieContent
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
     * @param  string $targetVersion
     * @return void
     */
    public function update($targetVersion = null)
    {
        try {
            $excuteList = array();
            if (isset($targetVersion)) {
                $this->setTargetVersion($targetVersion);
            }

            // 버전 실행목록 (현재 버전까지 필터링)
            $versionClass = new G5Version();
            $versionList = $versionClass->getExecuteVersionList($targetVersion);

            // 마이그레이션 파일 리스트 조회
            $migrationList = $this->getMigrationList();

            foreach ($migrationList as $script) {
                $scriptInfo = $this->getMigrationInfoByScriptfileName((string)$script);

                // 버전목록에 미포함 필터링
                if (!in_array($scriptInfo['version'], $versionList)) {
                    continue;
                }
                // 성공내역 필터링
                if ($this->getMigrationMethod() == "up") {
                    $statement = self::$mysqli->prepare("SELECT mi_id FROM g5_migrations WHERE mi_version = ? AND mi_result = 'success'");
                    if ($statement) {
                        $statement->bind_param("s", $scriptInfo['version']);
                        $statement->execute();
                        $statement->store_result();
                        if ($statement->num_rows() > 0) {
                            continue;
                        }
                    }
                }
                $excuteList[] = $scriptInfo;
            }
            // 정렬
            usort($excuteList, array("self", "sortByVersion"));

            // sql 실행
            $beforeVersion = "";
            $sort = 1;
            foreach ($excuteList as $script) {
                if ($beforeVersion == $script['version']) {
                    $sort++;
                } else {
                    $beforeVersion = $script['version'];
                    $sort = 1;
                }
                $result = $this->executeSqlScriptFile($this->getMigrationMethod(), (string)$script['fileName']);
                $result['sort'] = $sort;

                if ($this->getMigrationMethod() == "up") {
                    $this->insertMigrationLog($script, $result);
                } else {
                    if ($result['result'] == "success") {
                        $this->deleteMigrationLog($script);
                    }
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return string
     */
    public function getMigrationMethod()
    {
        if (empty($this->migrationMethod)) {
            $this->setMigrationMethod();
        }
        return $this->migrationMethod;
    }
    /**
     * @return void
     */
    public function setMigrationMethod()
    {
        if (isset(self::$targetVersion) && version_compare(G5Version::$currentVersion, self::$targetVersion, ">=")) {
            $this->migrationMethod = "down";
        } else {
            $this->migrationMethod = "up";
        }
    }

    /**
     * 마이그레이션 결과 저장
     *
     * @param  array<mixed>              $migration
     * @param  array<string, int|string> $result
     * @return void
     */
    public function insertMigrationLog($migration, $result)
    {
        $table = self::MIGRATION_TABLE;
        $sql = "INSERT INTO
                    {$table}
                SET
                    mi_version = ?,
                    mi_script = ?,
                    mi_result = ?,
                    mi_sort = ?,
                    mi_execution_date = NOW(),
                    mi_reason = ?";
        $insertLog = self::$mysqli->prepare($sql);
        $insertLog->bind_param("sssss", $migration['version'], $migration['fileName'], $result['result'], $result['sort'], $result['message']);
        $insertLog->execute();
    }

    /**
     * Migration 기록 삭제
     * @param array<string> $migration
     * @return void
     */
    public function deleteMigrationLog($migration)
    {
        $table = self::MIGRATION_TABLE;
        $sql = "DELETE FROM
                    {$table}
                WHERE
                    mi_version = ?
                    AND mi_script = ?
                    AND mi_result = 'success'
                ";
        $deleteLog = self::$mysqli->prepare($sql);
        $deleteLog->bind_param("ss", $migration['version'], $migration['fileName']);
        $deleteLog->execute();
    }

    /**
     * 버전기준 정렬
     *
     * @param  array<string> $a
     * @param  array<string> $b
     * @return int|bool
     */
    public function sortByVersion($a, $b)
    {
        if ($this->getMigrationMethod() == "up") {
            return version_compare($a["version"], $b["version"]);
        } else {
            return version_compare($b["version"], $a["version"]);
        }
    }

    /**
     * sql 스크립트 파일 목록 조회
     *
     * @return array<string>
     */
    public function getMigrationList()
    {
        if (empty($this->migrationList)) {
            if (is_dir(self::MIGRATION_PATH)) {
                if ($dirResource = @opendir(self::MIGRATION_PATH)) {
                    while (($fileName = readdir($dirResource)) !== false) {
                        if ($fileName == '.' || $fileName == '..') {
                            continue;
                        }
                        if ($this->isMigrationFile($fileName)) {
                            $this->migrationList[] = $fileName;
                        }
                    }
                    closedir($dirResource);
                }
            }
        }
        if (!$this->migrationList) {
            throw new Exception("migration 파일을 조회할 수 없습니다.");
        }

        return $this->migrationList;
    }

    /**
     * 파일명 => 마이그레이션 정보 추출
     *
     * @param  string $fileName
     * @return array<string>
     */
    public function getMigrationInfoByScriptfileName($fileName = null)
    {
        if (empty($fileName)) {
            throw new Exception("not exist fileName " . $fileName);
        }
        if (!$this->isMigrationFile($fileName)) {
            throw new Exception("유효한 파일 형식이 아닙니다.");
        }

        list($version, $summary) = explode("__", str_replace('.' . self::FILE_EXE, '', $fileName));

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
        return preg_match('/^v[a-z0-9-\.]+_{2}[\w]+\.' . self::FILE_EXE . '$/i', $fileName);
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

    /**
     * @return Mysqli
     */
    public function getMysqli()
    {
        return self::$mysqli;
    }

    /**
     * 최근 migration 버전 1건 조회
     * @return string
     */
    public function getMigrationVersion()
    {
        $table = self::MIGRATION_TABLE;
        $result = self::$mysqli->query("SELECT mi_version FROM {$table} ORDER BY mi_id DESC LIMIT 1");
        $row = $result->fetch_array(MYSQLI_ASSOC);

        return (string)$row['mi_version'];
    }
}
