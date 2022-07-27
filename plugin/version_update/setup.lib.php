<?php

class G5MigrationSetup extends G5Migration
{
    private const CREATE_TABLE_PATH  = parent::MIGRATION_PATH . "/core/create_migration_table.sql";

    public function __construct()
    {
    }

    /**
     * migration 테이블 체크
     *
     * @return bool
     */
    public function checkExistMigrationTable()
    {
        $table = parent::MIGRATION_TABLE;
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
        try {
            $createFile = file(self::CREATE_TABLE_PATH);

            if (!$createFile) {
                throw new Exception("스크립트 파일을 읽을 수 없습니다.\n경로 : " . self::CREATE_TABLE_PATH);
            }

            $createQuery = parent::setQueryFromScript($createFile);

            $result = parent::$mysqli->multi_query($createQuery);
            while (mysqli_more_results(parent::$mysqli)) {
                mysqli_next_result(parent::$mysqli);
            }

            if (!$result) {
                throw new Exception("테이블 생성에 실패했습니다.\n[" . parent::$mysqli->errno . "] " . parent::$mysqli->error);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
}
