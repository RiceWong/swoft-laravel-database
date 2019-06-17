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
    public $id;
    protected $queryTimeout = -1;
    protected $config;
    public function __construct($config) {
        $this->comysql = new Mysql();
        if ( array_search('query_timeout', $config)!== false ){
            $timeout = intval($config['query_timeout']);
            if ($timeout > 0){
                $timeout = $timeout / 1000;
            }
            $this->queryTimeout = $timeout;
            unset($config['query_timeout']);
        }
        $this->config = $config;
        $result = $this->comysql->connect($config);
        if ($result === false){
            $errno = $this->comysql->connect_errno;
            $error = $this->comysql->connect_error;
            throw new PDOException($error, $errno);
        }
    }

    protected function getComysql(){
        if ( !$this->comysql->connected ){
            $this->comysql->connect($this->config);
        }
        return $this->comysql;
    }
    protected function getTimeout($timeout=null){
        return $timeout === null ? $this->queryTimeout : $timeout;
    }

    public function setTimeout($timeout=null){
        $this->queryTimeout = $timeout/1000;
    }

    protected function throwException(){
        throw new PDOException($this->comysql->error, $this->comysql->errno);
    }
    /**
     * {@inheritdoc}
     * @param string $statement
     */
    public function exec($statement, $timeout=null) {
        $result = $this->getComysql()->query($statement, $this->getTimeout($timeout) );
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
        $stmt =  $this->getComysql()->prepare($prepareString);
        $stmt === false && $this->throwException();
        $stmt = new CoPDOStatement($stmt, $this->getTimeout());
        $stmt->sql = $prepareString;
        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, $timeout=null) {
        $result = $this->getComysql()->query($sql, $this->getTimeout($timeout) );
        $result === false && $this->throwException();
        $stmt = new CoPDOStatement(null);
        $stmt->setRows($result);
        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = PDO::PARAM_STR) {
        return $this->getComysql()->escape($input);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null) {
        return $this->comysql->insert_id;
    }

    public function beginTransaction() {
        return $this->getComysql()->begin();
    }

    public function commit() {
        return $this->getComysql()->commit();
    }

    public function rollBack() {
        return $this->getComysql()->rollback();
    }

    public function errorCode() {
        return $this->comysql->errno;
    }

    public function errorInfo() {
        return $this->comysql->error;
    }

    public function close(){
        return $this->comysql->close();
    }
}
