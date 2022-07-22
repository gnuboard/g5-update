<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
/**
 * 그누보드5 데이터베이스 업데이트
 * - 파일 명명 규칙 : {버전명}__{설명}.sql
 * 
 * @todo
 * 1. 마이그레이션 기본설정
 *  1) 테이블 생성 (Class G5MigrationSetup)
 *      - 테이블 존재여부 체크
 *      - 테이블 생성
 * 
 * 2. 마이그레이션 파일 실행
 *      - G5_DB_AUTO_UPDATE 체크 (true 일 경우에만 실행) 
 *      - DB 체크 및 업데이트 진행 여부 결정
 *      - 버전목록 조회 (Class G5MigrationVersion)
 *      - 마이그레이션 파일 리스트 조회
 *      - 파일명 => 유효 데이터 변환
 *      - 파일 리스트 정렬
 *      - 파일 실행
 * 
 * 3. 이력 저장
 * - 테이블 양식에 맞춰서 데이터 insert
 * 
 * 기타
 * 1. off의 경우 수동으로 할 수 있도록 (기존 db 업그레이드와 병합?)
 * 2. 트랜잭션
 * 3. 보안관련 체크필요
 * 4. 예외처리 추가
 */
class G5Migration
{
    protected static $mysqli;
    
    const AUTO_UPDATE               = true;
    public const CURRENT_VERSION    = "v" . G5_GNUBOARD_VER;
    public const MIGRATION_TABLE    = G5_TABLE_PREFIX . "migrations";
    public const MIGRATION_PATH     = G5_ADMIN_PATH . "/database_update";
    public const SCRIPT_PATH        = self::MIGRATION_PATH . "/migration";

    public function __construct()
    {
        try {
            echo "현재 그누보드 버전 : " . self::CURRENT_VERSION . "<br><br>";
            
            // mysqli connect
            self::$mysqli = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
            if (self::$mysqli->connect_error) {
                throw new Exception('데이터베이스 연결에 실패했습니다. Error Message : ' . self::$mysqli->connect_error);
            }
            
            // create migration table
            $this->initialSetup();

            if (self::AUTO_UPDATE) {
                $this->update();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * migration 초기설정
     */
    public function initialSetup()
    {
        $setup = new G5MigrationSetup();
        if (!$setup->checkExistMigrationTable()) {
            $setup->createMigrationTable();
        }
	}

    /**
     * SQL 스크립트 파일 실행
     * @param string $scriptFilePath 스크립트 파일 경로
     * @return array
     * @throws Exception
     */
    public function executeSqlScriptFile($scriptFilePath = null)
    {
        try {
            $query = "";
            $scriptFlieContent = "";

            if (empty($scriptFilePath) || !file_exists($scriptFilePath)) {
                throw new Exception("잘못된 스크립트 파일 경로입니다.\n경로 : " . $scriptFilePath);
            }

            $scriptFlieContent = file($scriptFilePath);
            if (!$scriptFlieContent) {
                throw new Exception("스크립트 파일을 읽을 수 없습니다.");
            }

            $query = $this->setQueryFromScript($scriptFlieContent);

            $result = self::$mysqli->multi_query($query);

            while(mysqli_more_results(self::$mysqli)) {
                mysqli_next_result(self::$mysqli);
            }

            if (!$result) {
                throw new Exception("query 실행이 실패했습니다.\n[" . self::$mysqli->errno . "] " . self::$mysqli->error);
            }

            return array("result" => "success", "message" => "success");
        } catch(Exception $exception) {
            echo $exception->getMessage();
            return array("result" => "fail", "message" => $exception->getMessage());
        }
    }

    /**
     * sql 파일 Query문을 사용자 설정에 맞게 변환
     * @param mixed $scriptFlieContent 
     * @return string
     */
    public static function setQueryFromScript($scriptFlieContent)
    {
        $query = "";

        $query = implode("\n", $scriptFlieContent);
        $query = preg_replace('/^--.*$/m', "", $query); 
        $query = preg_replace('/`g5_([^`]+`)/', '`' . G5_TABLE_PREFIX . '$1', $query);
        $query = get_db_create_replace($query);

        return $query;
    }
    
    /**
     * 업데이트 실행
     */
    public function update()
    {
        // 실행 전 migration 테이블 체크 후 실행여부 결정
        if ($this->checkMigrationTable()) {
            // 실행 스크립트 배열
            $excuteList = array(); 
            // 버전 실행목록
            $migrationVersion = new G5MigrationVersion();
            $versionList = $migrationVersion->getExecuteVersionList();
            // 마이그레이션 파일 리스트 조회
            $scriptList = $this->getMigrationScriptList();
            // 실행할 파일목록 필터링
            foreach ($scriptList as $script) {
                $migrationInfo = $this->getMigrationInfoByScriptfileName($script);
                if (in_array($migrationInfo['version'], $versionList)) {
                    $excuteList[] = $migrationInfo;
                }
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
        }
    }

    /**
     * 마이그레이션 결과 저장
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
     * @param array $a
     * @param array $b
     * @return int|bool
     */
    public function sortByVersion($a, $b)
    {
        return version_compare($a["version"], $b["version"]);
    }

    /**
     * 업데이트 실행여부 체크 
     * @todo 함수명이 애매해서 수정해야함.
     * 
     * @return bool
     */
    public function checkMigrationTable()
    {
        /**
         * v5.5.1 일때 v5.5.1-beta까지 스크립트가 있으면, 마지막이라고 체크하고 실행하지 않아야함.
         */

        return true;
    }

    /**
     * sql 스크립트 파일 목록 조회
     */
    public function getMigrationScriptList() 
    {
        $scriptList = array();

        if (is_dir(self::SCRIPT_PATH)) {
            if ($dirResource = @opendir(self::SCRIPT_PATH)) {
                while (($fileName = readdir($dirResource)) !== false) {
                    if ($fileName == '.' || $fileName == '..') {
                        continue;
                    }
                    if ($this->isMigrationFile($fileName)) {
                        $scriptList[] = $fileName;
                    }
                }
                closedir($dirResource);
            }
        }
        return $scriptList;
    }

    /**
     * 파일명 => 마이그레이션 정보 추출
     */
    public function getMigrationInfoByScriptfileName($fileName = null)
    {
        if (empty($fileName)) {
            throw new Exception("not exist fileName");
        }

        if ($this->isMigrationFile($fileName)) {
            list($version, $summary) = explode("__", str_replace('.sql', '', $fileName));

            return array("fileName" => $fileName, "version" => $version, "summary" => $summary);
            
        } else {
            throw new Exception("유효한 파일 형식이 아닙니다.");
        }
    }

    /**
     * 마이그레이션 파일인지 체크
     * @param string $fileName
     * @return int|false
     */
    public function isMigrationFile($fileName)
    {
        return preg_match('/^v[a-z0-9-\.]+_{2}[\w]+\.sql$/i', $fileName);
    }
}