<?php

/**
 * GitHub API Class
 *
 * @todo 사용자의 API요청횟수를 늘리기 위한 PAT(personal access token)를 관리자페이지에서 등록하는 방안
 */
class G5GithubApi
{
    private const API_TOKEN = "";

    private const API_URL = "https://api.github.com/repos/gnuboard/gnuboard5";
    private const API_VERSION_URL = self::API_URL . "/releases?per_page=";
    private const API_COMPARE_URL = self::API_URL . "/compare/";
    private const API_MODIFY_URL = self::API_URL . "/releases/tags/";

    public function __construct()
    {
    }

    /**
     * release(version) Data 조회
     *
     * @param  int $limit
     * @return Mixed
     */
    public static function getVersionData($limit = 100)
    {
        try {
            $url = self::API_VERSION_URL . $limit;

            $response = self::requestCurl($url);
            $response = json_decode((string)$response);

            if ($response == null) {
                throw new Exception("API결과를 변환하는데 실패했습니다.");
            }

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * 버전비교 Data 조회
     *
     * @param  string $param1 처음 버전
     * @param  string $param2 마지막 버전
     * @return Mixed
     */
    public static function getCompareData($param1 = null, $param2 = null)
    {
        try {
            if ($param1 == null || $param2 == null) {
                throw new Exception("parameter 값이 없습니다.");
            }

            $url = self::API_COMPARE_URL . $param1 . "..." . $param2;

            $response = self::requestCurl($url);
            $response = json_decode((string)$response);

            if ($response == null) {
                throw new Exception("API결과를 변환하는데 실패했습니다.");
            }

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * release 압축파일 다운로드
     *
     * @param  string $type    확장자 ('zip' or 'tar')
     * @param  string $version 다운로드 버전
     * @return Mixed
     */
    public static function getArchiveData($type = null, $version = null)
    {
        try {
            $validType = array("zip", "tar");

            if (!in_array($type, $validType)) {
                throw new Exception("유효하지 않은 확장자 입니다.");
            }
            if ($version == null) {
                throw new Exception("Version값이 없습니다.");
            }

            $url = self::API_URL . "/" . $type . "ball/" . $version;

            $response = self::requestCurl($url);

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * release 변경사항 Data 조회
     *
     * @param  string $tag Github repository tag (release)
     * @return Mixed
     */
    public static function getModifyData($tag = null)
    {
        try {
            if ($tag == null) {
                throw new Exception("tag 값이 없습니다.");
            }

            $url = self::API_MODIFY_URL . $tag;

            $response = self::requestCurl($url);
            $response = json_decode((string)$response);

            if ($response == null) {
                throw new Exception("API결과를 변환하는데 실패했습니다.");
            }

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * api.github 요청
     *
     * @param  string $url 요청 url
     * @return string|bool
     */
    private static function requestCurl($url)
    {
        $headerData = (self::API_TOKEN != null) ? "Authorization: token " . self::API_TOKEN : "";
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
                    $headerData
                ),
            )
        );

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception("(" . curl_errno($curl) . ") API 요청이 실패했습니다. 요청 URL : " . $url);
        }

        return $response;
    }
}