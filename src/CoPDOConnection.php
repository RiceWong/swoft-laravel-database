<?php

namespace SwoftLaravel\Database;

use PDO;
use function func_get_args;
use \Swoole\Coroutine\Mysql;
use SwoftLaravel\Database\Interfaces\PDOConnectionInterface;
use PDOException;
/**
 * PDO implementation of the Connection interface.
 * Used by all PDO-based drivers.
 */
class CoPDOConnection implements PDOConnectionInterface {
    protected $comysql;
    protected $id;
    public function __construct($config) {
        $this->comysql = new Mysql();
        $result = $this->comysql->connect($config);
        $this->id = time();
        if ($result === false){
            $errno = $this->comysql->connect_errno;
            $error = $this->comysql->connect_error;
            throw new PDOException($error, $errno);
        }
    }

    protected function throwException(){
        throw new PDOException($this->comysql->error, $this->comysql->errno);
    }
    /**
     * {@inheritdoc}
     * @param string $statement
     */
    public function exec($statement) {
        $result = $this->comysql->query($statement);
        $result === false && $this->throwException();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion() {
        return PDO::getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString, $driverOptions = []) {
        $timeout = null;
        if (array_key_exists('timeout', $driverOptions)){
            $timeout = $driverOptions['timeout'];
        }
        $stmt =  $this->comysql->prepare($prepareString, $timeout);
        $stmt === false && $this->throwException();
        return new CoPDOStatement($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, $timeout=null) {
        $stmt = $this->comysql->query($sql, $timeout);
        $stmt === false && $this->throwException();
        return new CoPDOStatement($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = PDO::PARAM_STR) {
        return $this->comysql->escape($input);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null) {
        return $this->comysql->insert_id;
    }

    public function beginTransaction() {
        return $this->comysql->begin();
    }

    public function commit() {
        return $this->comysql->commit();
    }

    public function rollBack() {
        return $this->comysql->rollback();
    }

    public function errorCode() {
        return $this->comysql->errno;
    }

    public function errorInfo() {
        return $this->comysql->error;
    }

    public function close(){
        var_export('close: '.$this->id);
        return $this->comysql->close();
    }
}
