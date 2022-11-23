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
     * @var mysqli
     */
    private static $_instance;

    public function __construct($charSet = 'utf8mb4')
    {
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

            $this->setInstance($mysqli);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * MySQL Prepared Statements
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
     * MySQL Prepared Statements
     * @todo ins/upd/del 영향받는 데이터가 출력되는 함수로 분리
     * @param string $sql       Statement to execute;
     * @param array $params     values of the parameters (if any)
     * @param boolean $close    true to close $stmt (in inserts) false to return an array with the values;   
     * @param string $types     a string with parameter types in case we'd decide to set them explicitly (almost never needed)
     * @return int|array
     */
    public function execSQL($sql, $params = array(), $close = false, $types = '')
    {
        try {
            $mysqli = $this->getInstance();

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
     * @todo 영향받는 행 출력 함수 생성
     */
    function affectedRow()
    {
        return $this->getInstance()->affected_rows;
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
     * Adding a field name in the ORDER BY clause based on the user's choice
     * @param string $value     the checked value. It is passed by reference so it won't raise an error in case a variable is not set. It would allow us to assign a default value if no value is provided.
     * @param array $allowed    the list of allowed values. the first one would serve as a default value
     * @param string $message   the error message to throw so a programmer would know what caused the error.
     * @return string
     * @throws InvalidArgumentException
     */
    function whiteList(&$value, $allowed, $message)
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
    function refValues(&$arr)
    {
        /**
         * @deprecated 버전 체크
         */
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     * @return int|string
     */
    function insertId()
    {
        return $this->getInstance()->insert_id;
    }

    /**
     * Get static instance of self
     *
     * @return  mysqli
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new G5Mysqli();
        }
        return self::$_instance;
    }

    /**
     * Set static instance of self
     *
     * @param  mysqli  $_instance  Static instance of self
     * @return  self
     */
    public static function setInstance(mysqli $_instance)
    {
        self::$_instance = $_instance;

        return self::class;
    }
}
