<?php

/**
 * Class Migration
 */
abstract class Migration
{
    /**
     * Table check query
     * @var mysqli_stmt
     * @deprecated Symbol '$tableCheckStmt' is declared but not used.
     */
    protected $tableCheckStmt;
    /**
     * Coulmn check query
     * @var mysqli_stmt
     */
    protected $columnCheckStmt;

    /**
     * @var Mysqli
     */
    protected $mysqli;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $g5Migration = new G5Migration();
        $this->mysqli = $g5Migration->getMysqli();

        // Table check query
        $tableCheckQuery = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
        $this->tableCheckStmt = $this->mysqli->prepare($tableCheckQuery);
        // Coulmn check query
        $columnCheckQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
        $this->columnCheckStmt = $this->mysqli->prepare($columnCheckQuery);
    }

    /**
     * Perform a migration step.
     *
     * @return void
     */
    abstract public function up();

    /**
     * Revert a migration step.
     *
     * @return void
     */
    abstract public function down();

    /**
     * 컬럼 존재여부 체크
     *
     * @param  string $table
     * @param  string $column
     * @return bool
     */
    public function existColumn($table, $column)
    {
        // 사용자 임의 테이블로 변경
        $table = $this->convertCustomSetting($table);

        $this->columnCheckStmt->bind_param("ss", $table, $column);

        if (!$this->columnCheckStmt->execute()) {
            echo $this->mysqli->errno;
            return false;
        }
        $this->columnCheckStmt->store_result();

        if ($this->columnCheckStmt->num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Query 실행
     *
     * @param  string $sql
     * @return void
     */
    public function executeQuery($sql)
    {
        $sql = $this->convertCustomSetting($sql);
        try {
            $this->mysqli->query($sql);
        } catch (mysqli_sql_exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * 사용자 임의설정으로 SQL문 변경
     *
     * @param  string $string   SQL문
     * @return string
     */
    public function convertCustomSetting($string)
    {
        $string = preg_replace('/`g5_([^`]+`)/', '`' . G5_TABLE_PREFIX . '$1', (string)$string);
        if (in_array(strtolower(G5_DB_ENGINE), array('innodb', 'myisam'))) {
            $string = preg_replace('/ENGINE=MyISAM/', 'ENGINE=' . G5_DB_ENGINE, (string)$string);
        } else {
            $string = preg_replace('/ENGINE=MyISAM/', '', (string)$string);
        }
        if (G5_DB_CHARSET !== 'utf8') {
            $string = preg_replace('/CHARSET=utf8/', 'CHARACTER SET ' . get_db_charset(G5_DB_CHARSET), (string)$string);
        }

        return (string)$string;
    }
}
