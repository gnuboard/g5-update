<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * 그누보드5 데이터베이스 업데이트
 * @todo
 * 0. 파일명명 규칙 확립(flyway 참고)
 *      - v5.5.8.1.1 / v5.5.0-beta
 * 
 * 1. 마이그레이션 테이블 생성 => [완료]
 *      - 테이블 존재여부 체크
 *      - 테이블 생성
 * 
 * 2. 마이그레이션 파일 실행
 *      - G5_DB_AUTO_UPDATE 체크 (true 일 경우에만 실행)
 *      - 버전목록 조회 
 *      - 마이그레이션 파일 리스트 조회
 *      - 파일명 => 유효 데이터 변환
 *      - 파일 리스트 정렬
 *      - 파일 실행
 * 
 * 3. 이력 저장
 * - 테이블 양식에 맞춰서 데이터 insert
 * 
 * 기타
 * 2. off의 경우 수동으로 할 수 있도록 (기존 db 업그레이드와 병합?)
 */
class G5Migration
{
    public $mysqli = null;
    public $tableStmtCheck = null;
    public $columnStmtCheck = null;
    
    public $versionList         = array();
    public $versionListReverse  = array();
    public $latestVersion       = null;

    const AUTO_UPDATE               = true;
    public const CURRENT_VERSION    = "v5.5.1";//G5_GNUBOARD_VER;
    public const MIGRATION_TABLE    = G5_TABLE_PREFIX . "migrations";
    public const MIGRATION_PATH     = G5_ADMIN_PATH . "/database_update";
    public const SCRIPT_PATH        = self::MIGRATION_PATH . "/migration";
    public const CREATE_TABLE_PATH  = self::MIGRATION_PATH . "/core/create_migration_table.sql";

    // token값이 없는 경우, 1시간에 60번의 데이터조회가 가능함
    private $token = "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";
    
    public function __construct()
    {
        // mysqli connect
        $this->mysqli   = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
        // Table check query
        $this->tableStmtCheck = $this->mysqli->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?");
        // Coulmn check query
        $this->columnStmtCheck = $this->mysqli->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?");

        // 테이블 생성
        $this->createMigrationTable();
        
        // 마이그레이션 체크
        echo "현재 그누보드 버전 : " . self::CURRENT_VERSION . "<br><br>";

        if (self::AUTO_UPDATE) {
            $this->update();
        }
    }

    /**
     * migration 테이블 생성
     * @return bool
     * @throws Exception
     */
    public function createMigrationTable() 
    {
        if (!$this->checkExistMigrationTable()) {
            $this->executeSqlScriptFile(self::CREATE_TABLE_PATH);

            return true;
        } else {
            return false;
        }
    }

    /**
     * migration 테이블 체크
     * @return bool
     */
    public function checkExistMigrationTable()
    {
        $table = self::MIGRATION_TABLE;

        $this->tableStmtCheck->bind_param("s", $table);
        $this->tableStmtCheck->execute();

        $result = $this->tableStmtCheck->get_result();
        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SQL 스크립트 파일 실행
     * @param string $scriptFilePath 스크립트 파일 경로
     * @return bool
     * @throws Exception
     */
    private function executeSqlScriptFile($scriptFilePath = null)
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
            $query = implode("\n", $scriptFlieContent);
            // -- 주석제거
            $query = preg_replace('/^--.*$/m', "", $query); 
            //사용자 환경설정 값으로 DDL 변경
            $query = preg_replace('/`g5_([^`]+`)/', '`' . G5_TABLE_PREFIX . '$1', $query);
            $query = get_db_create_replace($query);

            // execute multi query
            $result = $this->mysqli->multi_query($query);

            if (!$result) {
                throw new Exception("query 실행이 실패했습니다.\n[" . $this->mysqli->errno . "] " . $this->mysqli->error);
            }

            return true;
        } catch(Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * 버전목록 조회
     * 
     * @breif 버전 리스트 구성
     * 1. upgrade : 낮은버전 -> 높은버전으로 진행해야 Table 및 Column이 정상적인 흐름으로 변경이 됨.
     * 2. downgrade : upgrade의 역순으로 진행.
     * 
     * @todo 추후 update.lib.php가 정식버전에 포함된다면 통합
     */
    public function getVersionList()
    {
        $result = $this->getReleasesListFromGithubApi();
        
        if ($result == false) {
            return false;
        }
        foreach ($result as $key => $var) {
            if (!isset($var->tag_name)) {
                continue;
            }

            $this->versionList[] = $var->tag_name;
        }
        if (isset($this->versionList[0])) {
            $this->latestVersion = $this->versionList[0];
        }
        // 내림차순
        $this->versionListReverse = array_reverse($this->versionList);
    }

    /**
     * github api
     * @todo 추후 update.lib.php가 정식버전에 포함된다면 getApiCurlResult로 통합.
     */
    public function getReleasesListFromGithubApi()
    {
        // query_paramter : per_page (defalut = 30)
        $url    = "https://api.github.com/repos/gnuboard/gnuboard5/releases?per_page=100";
        $auth   = ($this->token != null) ? "Authorization: token " . $this->token : "";

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
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_FAILONERROR => true,
                CURLOPT_HTTPHEADER => array(
                    $auth
                ),
            )
        );
        $response = curl_exec($curl);
        
        if (curl_errno($curl)) {
            return false;
        }
        return json_decode((string)$response);
    }

    /***********************************************개발 진행 중*******************************************************/
    
    /**
     * 업데이트 실행
     */
    public function update()
    {
        // 실행 전 migration 테이블 체크 후 실행여부 결정
        if ($this->checkMigrationTable()) {
            // 버전목록 초기화
            $this->getVersionList();
            // 업데이트 진행 버전목록 조회
            $executeList = $this->getExecuteVersionList();
            // 마이그레이션 파일 리스트 조회
            $scriptList = $this->getMigrationScriptList();

            print_r($executeList);
            echo "<br>";
            print_r($scriptList);

            // 비교
        }
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
                    if (preg_match('/.sql/i', $fileName)) {
                        $scriptList[] = $fileName;
                    }
                }
                closedir($dirResource);
            }
        }
        /**
         * @todo 파일을 오래된 순서대로 정렬해야함.
         */
        // uksort($scriptList, "version_compare");

        return $scriptList;
    }
    

    /**
     * 실행할 버전 목록 조회
     */
    public function getExecuteVersionList()
    {
        $versionList = array();

        if (!is_array($this->versionListReverse)) {
            throw new Exception("전체 버전목록이 없습니다");
        }

        foreach ($this->versionListReverse as $version) {
            if (version_compare($version, self::CURRENT_VERSION) <= 0) {
                $versionList[] = $version;
            }
        }
        return $versionList;
    }



    /***********************************************개발 예정*******************************************************/

    /**
     * 파일명 => 마이그레이션 정보 추출
     * @todo 파일명 규칙이 정해져야 개발가능
     */
    public function getMigrationInfoByScriptFileName()
    {

    }

    /**
     * 컬럼 존재여부 체크 
     * @todo 파일형식으로 실행하기때문에 안쓰일것 같음.
     */
    public function checkExistColumn($table, $column)
    {
        if ($this->columnStmtCheck) {
            $this->columnStmtCheck->bind_param("ss", $table, $column);
            $this->columnStmtCheck->execute();
    
            $result = $this->columnStmtCheck->get_result();
            
            if ($result->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
}