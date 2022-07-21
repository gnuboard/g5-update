<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
/**
 * 그누보드5 데이터베이스 업데이트
 * - 파일 명명 규칙 : {버전명}__{설명}.sql
 * 
 * @todo
 * 1. 마이그레이션 테이블 생성 => [완료]
 *      - 테이블 존재여부 체크
 *      - 테이블 생성
 * 
 * 2. 마이그레이션 파일 실행
 *      - G5_DB_AUTO_UPDATE 체크 (true 일 경우에만 실행) 
 *      - DB 체크 및 업데이트 진행 여부 결정
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
 * 1. off의 경우 수동으로 할 수 있도록 (기존 db 업그레이드와 병합?)
 * 2. 트랜잭션
 * 3. 보안관련 체크필요
 * 4. 예외처리 추가
 * 5. 버전목록 API 요청 => 파일로 저장해서 조회하는방법
 */
class G5Migration
{
    public $mysqli;
    
    public $versionList         = array();
    public $versionListReverse  = array();
    public $latestVersion       = null;

    const AUTO_UPDATE               = true;
    public const CURRENT_VERSION    = "v5.5.1";//G5_GNUBOARD_VER;
    public const MIGRATION_TABLE    = G5_TABLE_PREFIX . "migrations";
    public const MIGRATION_PATH     = G5_ADMIN_PATH . "/database_update";
    public const SCRIPT_PATH        = self::MIGRATION_PATH . "/migration";

    // token값이 없는 경우, 1시간에 60번의 데이터조회가 가능함
    private $token = "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";
    
    public function __construct()
    {
        try {
            echo "현재 그누보드 버전 : " . self::CURRENT_VERSION . "<br><br>";

            // mysqli connect
            $this->mysqli   = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
            if ($this->mysqli->connect_error) {
                throw new Exception('데이터베이스 연결에 실패했습니다. Error Message : ' . $this->mysqli->connect_error);
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
     * - migration Table 생성
     */
    protected function initialSetup()
    {
        $setup = new G5MigrationSetup($this->mysqli);
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

            $result = $this->mysqli->multi_query($query);

            while(mysqli_more_results($this->mysqli)) {
                mysqli_next_result($this->mysqli);
            }

            if (!$result) {
                throw new Exception("query 실행이 실패했습니다.\n[" . $this->mysqli->errno . "] " . $this->mysqli->error);
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
     * 버전목록 조회
     * 
     * @breif 버전 리스트 구성
     * 1. upgrade : 낮은버전 -> 높은버전으로 진행해야 Table 및 Column이 정상적인 흐름으로 변경이 됨.
     * 2. downgrade : upgrade의 역순으로 진행.
     * 
     * @todo 추후 update.lib.php가 정식버전에 포함된다면 통합
     * @todo 항상 api로 버전을 불러오지 않아도 될거같음... => 버전목록이 추가가 될 뿐 변하지 않으므로 => 파일로 저장해서 불러오기, 없으면 api 조회
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
    
    /**
     * 업데이트 실행
     */
    public function update()
    {
        // 실행 전 migration 테이블 체크 후 실행여부 결정
        if ($this->checkMigrationTable()) {
            $excuteList = array(); // 실행 스크립트 배열
            // 버전목록 초기화
            $this->getVersionList();
            // 업데이트 진행 버전목록 조회
            $versionList = $this->getExecuteVersionList();
            // 마이그레이션 파일 리스트 조회
            $scriptList = $this->getMigrationScriptList();
            
            // 실행할 파일목록 필터링
            foreach ($scriptList as $script) {
                $migrationInfo = $this->getMigrationInfoByScriptFileName($script);
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
                $result = $this->executeSqlScriptFile(self::SCRIPT_PATH . "/" . $script['filename']);
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
                    mi_sort = ?,
                    mi_script = ?,
                    mi_result = ?,
                    mi_execution_date = NOW()";
        $insertLog = $this->mysqli->prepare($sql);
        $insertLog->bind_param("ssss", $script['version'], $result['sort'], $script['filename'], $result['result']);
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
                    if (preg_match('/.sql/i', $fileName)) {
                        $scriptList[] = $fileName;
                    }
                }
                closedir($dirResource);
            }
        }
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

    /**
     * 파일명 => 마이그레이션 정보 추출
     */
    public function getMigrationInfoByScriptFileName($filename = null)
    {
        if (empty($filename)) {
            throw new Exception("not exist Filename");
        }
        
        $fileInfo = preg_split('/__/', str_replace(".sql", "", $filename));

        if (empty($fileInfo[0]) || empty($fileInfo[1])) {
            throw new Exception("유효한 파일명 형식이 아닙니다.");
        }
        
        return array("filename" => $filename, "version" => $fileInfo[0], "summary" => $fileInfo[1]);
    }

    /***********************************************개발 예정*******************************************************/
    
}