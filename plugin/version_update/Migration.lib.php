<?php
/**
 * Class Migration
 */
abstract class Migration
{
    /**
     * Table check query
     * @deprecated
     */
    protected $tableCheckStmt;
    /**
     * Coulmn check query
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
        $this->tableCheckStmt = $this->mysqli->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?");
        // Coulmn check query
        $this->columnCheckStmt = $this->mysqli->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?");
    }

    /**
     * Perform a migration step.
     */
    abstract public function up();

    /**
     * Revert a migration step.
     */
    abstract public function down();
    
    /**
     * 컬럼 존재여부 체크
     */
    public function existColumn($table, $column)
    {
        // 사용자 임의 테이블로 변경
        $table = $this->convertCustomSetting($table);

        if ($this->columnCheckStmt) {
            $this->columnCheckStmt->bind_param("ss", $table, $column);

            if (!$this->columnCheckStmt->execute()){
                echo $this->mysqli->errno;
            }

            if ($this->columnCheckStmt->get_result()->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $sql
     */
    public function executeQuery($sql)
    {
        // 사용자 임의설정으로 Query 변경
        $sql = $this->convertCustomSetting($sql);

        $this->mysqli->query($sql);
    }

    /**
     * @param string $string
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
