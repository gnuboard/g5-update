<?php

/**
 * DB업데이트 기본설정 Class
 */
class G5MigrationSetup extends G5Migration
{
    private $createTablePath;

    public function __construct()
    {
        $this->createTablePath = parent::$updatePath . "/core/create_migration_table.sql"; 
    }

    /**
     * migration 테이블 체크
     *
     * @return bool
     */
    public function checkExistMigrationTable()
    {
        $table = parent::$migrationTable;
        $statement = parent::$mysqli->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?");
        $statement->bind_param("s", $table);
        $statement->execute();
        $statement->store_result();
        if ($statement->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * migration 테이블 생성
     *
     * @return void
     * @throws Exception
     */
    public function createMigrationTable()
    {
        $createFile = file($this->createTablePath);

        if (!$createFile) {
            throw new Exception("스크립트 파일을 읽을 수 없습니다.\n경로 : " . $this->createTablePath);
        }

        $createQuery = $this->setQueryFromScript($createFile);

        $result = parent::$mysqli->multi_query($createQuery);
        while (mysqli_more_results(parent::$mysqli)) {
            mysqli_next_result(parent::$mysqli);
        }

        if (!$result) {
            throw new Exception("테이블 생성에 실패했습니다.\n[" . parent::$mysqli->errno . "] " . parent::$mysqli->error);
        }
    }

    /**
     * .sql파일의 SQL문을 사용자 설정으로 변환
     *
     * @param  array<string> $scriptFlieContent     .sql파일 내용
     * @return string
     */
    public function setQueryFromScript($scriptFlieContent)
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
}
