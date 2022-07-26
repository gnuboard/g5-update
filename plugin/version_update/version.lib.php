<?php

/**
 * 그누보드 버전 Class
 * @todo 
 * 1. VERSION_LIST_PATH 경로 설정 
 * 2. version.json 파일 권한으로 인해 덮어쓰기 불가
 * 3. github api를 별도 class로 분리해서 사용 => 버전 업데이트에도 쓰임.
 * 
 */
class G5MigrationVersion extends G5Migration
{
    public $latestVersion;
    public static $versionList = array();

    private const VERSION_LIST_PATH  = G5_DATA_PATH  . "/version.json"; // 임시경로

    public function __construct()
    {
        if (!$this->existFile() || $this->compareVersion() === 1) {
            $this->createFile();
        } else {
            self::$versionList = self::getVersionList();
        }
    }
    /**
     * 현재버전과 파일 최신버전 비교
     * @return int -1 or 0 or 1
     */
    public function compareVersion()
    {
        return version_compare(parent::CURRENT_VERSION, $this->getLastedVersionByFile());
    }

    /**
     * 마지막버전 조회
     * @return string
     */
    public function getLastedVersionByFile()
    {
        try {
            $versionList = self::getVersionList();
            if (!$versionList) {
                throw new Exception("error");
            }
            return end($versionList);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * version file 유무 체크
     * @return bool
     */
    private function existFile()
    {
        $filepath = self::VERSION_LIST_PATH;
        if (empty($filepath) || !file_exists($filepath)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * version file 생성
     * @return void
     */
    private function createFile()
    {
        try {
            $filePoint = fopen(self::VERSION_LIST_PATH, 'w+');
            if ($filePoint == false) {
                throw new Exception('버전파일 생성에 실패했습니다.');
            }

            $versionList = $this->getLatestVersionList();

            fwrite($filePoint, json_encode($versionList));
            fclose($filePoint);

            self::$versionList = $versionList;

        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 버전목록 조회
     * @breif upgrade : 낮은버전 -> 높은버전으로 진행해야 Table 및 Column이 정상적인 흐름으로 변경이 됨.
     * 
     */
    public function getLatestVersionList()
    {
        try {
            $result = G5GithubApi::getVersionData();
            
            if ($result == false) {
                return false;
            }
            foreach ($result as $var) {
                if (!isset($var->tag_name)) {
                    continue;
                }
    
                $this->versionList[] = $var->tag_name;
            }
            if (isset($this->versionList[0])) {
                $this->latestVersion = $this->versionList[0];
            }
            // 내림차순
            return array_reverse($this->versionList);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    /**
     * json file => php array data
     */
    public static function convertVersionFile()
    {
        return json_decode(file_get_contents(self::VERSION_LIST_PATH), true);
    }

    /**
     * 실행할 버전 목록 조회
     */
    public static function getExecuteVersionList()
    {
        $list = array();
        $versionList = self::getVersionList();

        if (!is_array($versionList)) {
            throw new Exception("전체 버전목록이 없습니다");
        }

        foreach ($versionList as $version) {
            if (version_compare($version, parent::CURRENT_VERSION) <= 0) {
                $list[] = $version;
            }
        }
        return $list;
    }

    public function setVersionList($versionList)
    {
        self::$versionList = $versionList;
    }

    public static function getVersionList()
    {
        if (empty(self::$versionList)) {
            self::$versionList = self::convertVersionFile();
        }
        return self::$versionList;
    }

    
}
