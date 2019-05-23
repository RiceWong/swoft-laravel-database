<?php
namespace SwoftLaravel\Database;
use Swoole\Coroutine\MySQL\Statement;

class CoPDOStatement extends \PDOStatement {
    protected $stmt;
    protected $values = [];
    public function __construct(Statement $stmt) {
        $this->stmt = $stmt;
    }

    public function execute ($params=null) {
        if ($params == null){
            $params = $this->values;
        }
        return $this->stmt->execute($params);
    }
//
    public function fetch ($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        return $this->stmt->fetch();
    }
//
//    public function bindParam ($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null) {}
//
//    public function bindColumn ($column, &$param, $type = null, $maxlen = null, $driverdata = null) {}
//
    public function bindValue ($parameter, $value, $data_type = PDO::PARAM_STR) {
        $this->values[] = $value;
    }
//
//    public function rowCount () {}
//    public function fetchColumn ($column_number = 0) {}
    public function fetchAll ($fetch_style = null, $fetch_argument = null,  $ctor_args = array()) {
        return $this->stmt->fetchAll();
    }
//    public function fetchObject ($class_name = "stdClass", array $ctor_args = array()) {}
//    public function errorCode () {}
//    public function errorInfo () {}
//    public function setAttribute ($attribute, $value) {}
//    public function getAttribute ($attribute) {}
//    public function columnCount () {}
//    public function getColumnMeta ($column) {}
//    public function setFetchMode ($mode, $classNameObject = null, array $ctorarfg = array()) {}
//    public function nextRowset () {}
//    public function closeCursor () {}
//    public function debugDumpParams () {}
//    final public function __wakeup () {}
//    final public function __sleep () {}
}