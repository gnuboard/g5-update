<?php
/**
 * @link https://www.php.net/manual/en/class.mysqli.php
 * @link https://github.com/ThingEngineer/PHP-MySQLi-Database-Class/blob/master/MysqliDb.php
 * @link https://phpdelusions.net/mysqli
 */
class G5Mysqli
{
    /**
     * Static instance of self
     *
     * @var self
     */
    protected static $_instance;

    /**
     * Static instance of self
     *
     * @var mysqli
     */
    private static $connection;

    //TODO $charset 설정옮기기
    private function __construct($charSet = 'utf8mb4')
    {
        if (isset($GLOBALS['g5']['connect_db'])) {
            self::setConnection($GLOBALS['g5']['connect_db']);
        } else {
            try {
                /* connection */
                $mysqli = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
                if ($mysqli->connect_errno) {
                    throw new Exception('[' . $mysqli->connect_errno . '] Mysqli Connection Error : ' . $mysqli->connect_error);
                }

                /* Set the desired charset after establishing a connection */
                if (isset($charSet)) {
                    $mysqli->set_charset($charSet);
                    if ($mysqli->errno) {
                        throw new Exception('Mysqli Error: ' . $mysqli->error);
                    }
                }

                self::setConnection($mysqli);
            } catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }

    }

    /**
     * @return void
     */
    private function __clone()
    {
        // 클론 방지
    }

    /**
     * MySQL Prepared Statements
     * @param string $sql       Statement to execute;
     * @param array $params     values of the parameters (if any)
     * @param boolean $close    true to close $stmt (in inserts) false to return an array with the values;   
     * @param string $types     a string with parameter types in case we'd decide to set them explicitly (almost never needed)
     * @return int|array
     */
    public function execSQL($sql, $params = array(), $close = false, $types = '')
    {
        try {
            $mysqli = self::$connection;
            $stmt = $mysqli->prepare($sql);
            if ($mysqli->error) {
                throw new Exception($mysqli->error);
            }
            if (count($params) > 0) {
                $types = $types ? $types : str_repeat('s', count($params));
                array_unshift($params, $types);
                if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params))) {
                    throw new Exception('parameter Error.');
                }
            }
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            if ($close) {
                $stmt->store_result();
                return $mysqli->affected_rows;
            } else {
                return $this->execSqlResult($stmt);
            }
        } catch (Exception $e) {
            echo '[Exception] ' . $e->getMessage();
            exit;
        }
    }

    /**
     * MySQL Prepared Statements Result
     * @param mysqli_stmt $stmt     Represents a prepared statement.
     * @return array
     */
    public function execSqlResult($stmt)
    {
        $result = array();
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {

            $meta = $stmt->result_metadata();
            while ($field = $meta->fetch_field()) {
                $parameters[] = &$row[$field->name];
            }

            call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));

            while ($stmt->fetch()) {
                $x = array();
                foreach ($row as $key => $val) {
                    $x[$key] = $val;
                }
                array_push($result, $x);
            }
        }
        $stmt->free_result();
        $stmt->close();

        return $result;
    }

    /**
     * Get 1 row from MySQL Prepared Statements Result
     * @param string $sql       Statement to execute;
     * @param array $params     values of the parameters (if any)
     * @param string $types     a string with parameter types in case we'd decide to set them explicitly (almost never needed)
     * @return array|null
     */
    public function getOne($sql, $params = array(), $types = '')
    {
        $result = $this->execSQL($sql, $params, false, $types);
        return isset($result[0]) ? $result[0] : null;
    }
    
    /**
     * Insert query using Mysqli Prepared Statements
     * @param string    $table      Table name in database
     * @param array     $data       Insert Data
     * @return bool
     */
    public function insertSQL($table, $data)
    {
        try {
            if (count($data) <= 0) {
                throw new Exception('Data is empty for insert');
            }
            $keys = array_keys($data);
            $keys = array_map(array($this, 'escapeMysqlIdentifier'), $keys);
    
            $table          = $this->escapeMysqlIdentifier($table);
            $fields         = implode(",", $keys);
            $placeholders   = str_repeat('?,', count($keys) - 1) . '?';
    
            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
    
            $result = $this->execSQL($sql, array_values($data), true);
    
            if ($result > 0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Update query using Mysqli Prepared Statements
     * @param string        $table          Table name in database
     * @param array         $data           Update Data
     * @param array         $conditional    Conditional for update (Associative Arrays)
     * @return bool
     */
    public function updateSQL($table, $data, $conditional)
    {
        try {
            if (count($data) <= 0) {
                throw new Exception('Data is empty for update');
            }
            if (empty($conditional) || count($conditional) <= 0) {
                throw new Exception('Conditional is empty for update');
            }

            $sql = "UPDATE {$this->escapeMysqlIdentifier($table)} SET ";
        
            // Add Update Column
            $column = array();
            foreach (array_keys($data) as $field) {
                array_push($column, "{$this->escapeMysqlIdentifier($field)} = ?");
            }
            $sql .= implode(', ', $column);

            // Add Conditional 
            $where = array();
            foreach ($conditional as $column => $val) {
                // Column
                array_push($where, " {$this->escapeMysqlIdentifier($column)} = ?");
                // Value
                $data[$column] = $val;
            }
            $sql .= " WHERE " . implode(' AND', $where) . "";

            $result = $this->execSQL($sql, array_values($data), true);
    
            if ($result > 0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Adding a field name in the ORDER BY clause based on the user's choice
     * @param string $value     the checked value. It is passed by reference so it won't raise an error in case a variable is not set. It would allow us to assign a default value if no value is provided.
     * @param array $allowed    the list of allowed values. the first one would serve as a default value
     * @param string $message   the error message to throw so a programmer would know what caused the error.
     * @return string
     * @throws InvalidArgumentException
     */
    public function whiteList(&$value, $allowed, $message)
    {
        try {
            if ($value === null || $value === '') {
                return $allowed[0];
            }
            $key = array_search($value, $allowed, true);
            if ($key === false) {
                throw new InvalidArgumentException($message);
            } else {
                return $value;
            }
        } catch (InvalidArgumentException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Referenced data array is required by mysqli since PHP 5.3+
     * @param array $arr
     * @return array
     */
    public function refValues(&$arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * escape MySQL identifiers
     * all identifiers must be quoted and escaped, according to MySQL standards, in order to avoid various syntax issues.
     * @param string    $field  Query Field
     * @return string
     * @link https://phpdelusions.net/mysqli_examples/insert
     */
    public function escapeMysqlIdentifier($field)
    {
        return "`" . str_replace("`", "``", $field) . "`";
    }

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     * @return int|string
     */
    public function insertId()
    {
        return self::$connection->insert_id;
    }

    /**
     * Gets the number of affected rows in a previous MySQL operation
     * @return int -1 indicates that the query returned an error or that mysqli_affected_rows() was called for an unbuffered SELECT query.
     */
    public function affectedRow()
    {
        return self::$connection->affected_rows;
    }

    /**
     * Get static instance of self
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Set static instance of self
     *
     * @param mysqli $mysqli
     * @return  void
     */
    public static function setConnection(mysqli $mysqli)
    {
        self::$connection = $mysqli;
    }
}
