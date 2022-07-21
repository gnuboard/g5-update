<?php

class G5MigrationSetup {

    public $mysqli = null;
    public const CREATE_TABLE_PATH  = G5Migration::MIGRATION_PATH . "/core/create_migration_table.sql";

    public function __construct($mysqli)
    {
        try {
            $this->mysqli = $mysqli;
            
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * migration 테이블 체크
     * @return bool
     */
    public function checkExistMigrationTable()
    {
        $table = G5Migration::MIGRATION_TABLE;
        $statement = $this->mysqli->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?");
        $statement->bind_param("s", $table);
        $statement->execute();
        $statement->store_result();
        if ($statement->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
        $statement->close();
    }

    /**
     * migration 테이블 생성
     * @return bool
     * @throws Exception
     */
    public function createMigrationTable() 
    {
        try {
            $createFile = file(self::CREATE_TABLE_PATH);

            if (!isset($createFile)) {
                throw new Exception("스크립트 파일을 읽을 수 없습니다.\n경로 : " . self::CREATE_TABLE_PATH);
            }
        
            $createQuery = G5Migration::setQueryFromScript($createFile);

            $result = $this->mysqli->multi_query($createQuery);
            while(mysqli_more_results($this->mysqli)) {
                mysqli_next_result($this->mysqli);
            }

            if (!$result) {
                throw new Exception("query 실행이 실패했습니다.\n[" . $this->mysqli->errno . "] " . $this->mysqli->error);
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}