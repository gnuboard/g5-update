<?php
/**
 * @link https://www.php.net/manual/en/class.mysqli.php
 * @link https://github.com/ThingEngineer/PHP-MySQLi-Database-Class/blob/master/MysqliDb.php
 */
class G5Mysqli
{
    /**
     * Static instance of self
     *
     * @var mysqli
     */
    protected static $_instance;

    public function __construct($mysqli = null, $charSet = 'utf8mb4')
    {
        try {
            if (!$mysqli) {
                // connection
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
     * @param array $params     array of type and values of the parameters (if any)
     * @param string $close     true to close $stmt (in inserts) false to return an array with the values;
     * @return int|array
     */
    public function execSQL($sql, $params, $close = false)
    {
        try {
            $mysqli = $this->getInstance();

            $stmt = $mysqli->prepare($sql);
            if ($mysqli->error) {
                throw new Exception($mysqli->error);
            }
            if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params))) {
                throw new Exception("Error.");
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
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $getResult = $stmt->get_result();
            while ($row = $getResult->fetch_assoc()) {
                array_push($result, $row);
            }
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

        $stmt->close();
        // $mysqli->close();

        return $result;
    }

    /**
     * Referenced data array is required by mysqli since PHP 5.3+
     * @param array $arr
     * @return array
     */
    function refValues(&$arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0)
        {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * Get static instance of self
     *
     * @return  mysqli
     */
    public function getInstance()
    {
        return $this->_instance;
    }

    /**
     * Set static instance of self
     *
     * @param  mysqli  $_instance  Static instance of self
     * @return  self
     */
    public function setInstance(mysqli $_instance)
    {
        $this->_instance = $_instance;

        return $this;
    }
}
