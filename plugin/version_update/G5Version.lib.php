<?php

/**
 * 그누보드 버전 Class
 */
class G5Version
{
    public static string $currentVersion = "v" . G5_GNUBOARD_VER;
    public static string $latestVersion;
    public static array $versionList;

    private const VERSION_LIST_PATH  = G5_DATA_PATH  . "/version.json";

    public function __construct()
    {
        // Set $versionList, $latestVersion
        $this->initLatestVersion();

        if (!$this->existFile() || $this->compareVersion() !== 0) {
            $this->createFile();
        }
    }

    /**
     * 버전목록, 최신버전 조회 및 초기화
     *
     * @return void
     */
    private function initLatestVersion()
    {
        $versionList = array();
        $versionData = G5GithubApi::getVersionData();
        foreach ($versionData as $data) {
            if (!isset($data->tag_name)) {
                continue;
            }
            // 버전형식 체크 및 beta버전 제외
            if (!preg_match('/^v[a-z0-9\.]+$/i', $data->tag_name)){
                continue;
            }
            $versionList[] = $data->tag_name;
        }

        $this->setVersionList($versionList);

        $lastestVersion = isset($versionList[0]) ? $versionList[0] : "";
        $this->setLatestVersion($lastestVersion);
    }

    /**
     * version file 유무 체크
     *
     * @return bool
     */
    private function existFile()
    {
        if (file_exists(self::VERSION_LIST_PATH)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 현재버전과 파일 최신버전 비교
     *
     * @return int -1 or 0 or 1
     */
    private function compareVersion()
    {
        return version_compare(self::$currentVersion, $this->getLatestVersion());
    }

    /**
     * version 파일 생성
     *
     * @return void
     */
    private function createFile()
    {
        try {
            if (empty($this->getVersionList())) {
                throw new Exception('버전정보가 없습니다.');
            }
            $filePoint = fopen(self::VERSION_LIST_PATH, 'w+');
            if ($filePoint == false) {
                throw new Exception('버전파일 생성에 실패했습니다.');
            }
            fwrite($filePoint, (string)json_encode($this->getVersionList()));
            fclose($filePoint);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * json file => php array data
     *
     * @return mixed
     */
    public static function convertVersionFile()
    {
        return json_decode((string)file_get_contents(self::VERSION_LIST_PATH), true);
    }

    /**
     * 실행할 버전 목록 조회
     *
     * @param   string $targetVersion 목표버전(null일 경우 현재버전까지 전체 업데이트)
     * @return  array<int, string>
     */
    public static function getExecuteVersionList($targetVersion = null)
    {
        $list = array();
        $versionList = self::getVersionListByFile();
        if (!is_array($versionList)) {
            throw new Exception("전체 버전목록이 없습니다");
        }
        foreach ((array)$versionList as $version) {
            // 현재버전 ~ 목표버전
            if ($targetVersion) {
                if (version_compare($targetVersion, self::$currentVersion, ">")) {
                    if (version_compare($version, self::$currentVersion, ">")
                        && version_compare($version, $targetVersion, "<=")) {
                        array_unshift($list, $version);
                    }
                } else {
                    if (version_compare($version, self::$currentVersion, "<=")
                        && version_compare($version, $targetVersion, ">")) {
                        $list[] = $version;
                    }
                }
            // 현재버전 이하
            } else {
                if (version_compare($version, self::$currentVersion) <= 0) {
                    $list[] = $version;
                }
            }
        }
        
        if (!$list) {
            throw new Exception("버전목록을 조회할 수 없습니다.");
        }

        return $list;
    }

    /**
     * Set versionList
     *
     * @param  array<int,string> $versionList
     * @return void
     */
    public function setVersionList($versionList)
    {
        self::$versionList = $versionList;
    }
    /**
     * Get versionList
     *
     * @return array<int,string> $versionList
     */
    public static function getVersionList()
    {
        return self::$versionList;
    }
    /**
     * VERSION_LIST_PATH 파일에서 최신버전목록 조회
     *
     * @return array<string>
     */
    public static function getVersionListByFile()
    {
        if (empty(self::$versionList)) {
            self::$versionList = self::convertVersionFile();
        }
        return self::$versionList;
    }
    /**
     * Get latestVersion
     *
     * @return string
     */
    public static function getLatestVersion()
    {
        return self::$latestVersion;
    }
    /**
     * Set latestVersion
     *
     * @param  string $version
     * @return void
     */
    public function setLatestVersion($version)
    {
        self::$latestVersion = $version;
    }
    /**
     * VERSION_LIST_PATH 파일에서 최신버전 조회
     *
     * @return string
     */
    public static function getLatestVersionByFile()
    {
        if (empty(self::$latestVersion)) {
            $versionList = (array)self::convertVersionFile();
            self::$latestVersion = $versionList[0];
        }
        return self::$latestVersion;
    }
}
