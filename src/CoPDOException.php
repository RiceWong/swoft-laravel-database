<?php


namespace SwoftLaravel\Database;


use \PDOException;
use Throwable;

class CoPDOException extends PDOException{
    public $elapsed;
    public $timeout;
    public function __construct($message='', $elapsed, $timeout, Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->elapsed = $elapsed;
        $this->timeout = $timeout;
    }
    public function setMysqlError($connection){
        $pdo = $connection->getPdo();
        $this->code = $pdo->errorCode();
        $this->message = $pdo->errorInfo();
    }
}