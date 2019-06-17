<?php
namespace SwoftLaravel\Database;
use Swoole\Coroutine\MySQL\Statement;
use \PDO;
use Swoole\Exception;

class CoPDOStatement extends \PDOStatement {
    protected $stmt;
    protected $timeout= -1 ;
    protected $values = [];
    protected $rows=null;
    public $sql;
    // 新增一个参数用来控制最大查询时间，默认无限制
    public function __construct(Statement $stmt=null, $timeout=null) {
        $this->stmt = $stmt;
        $this->timeout = $timeout;
    }
    // 受限不支持的函数
    public function bindParam ($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null) {
        throw new CoPDOException('unsupported feature');
    }
    // 受限不支持的函数
    public function bindColumn ($column, &$param, $type = null, $maxlen = null, $driverdata = null) {
        throw new CoPDOException('unsupported feature');
    }
    // 受限不支持的函数
    public function fetchColumn ($column_number = 0) {
        throw new CoPDOException('unsupported feature');
    }
    // 受限不支持的函数
    public function fetchObject ($class_name = "stdClass", $ctor_args = array()) {
        throw new CoPDOException('unsupported feature');
    }
    // 由于 comysql statement 与 pdo statement 实现不同, 这里采用数组形式模拟参数绑定
    public function bindValue ($parameter, $value, $data_type = \PDO::PARAM_STR) {
        $this->values[] = $value;
    }
    public function setRows($rows=[]){
        $this->rows = $rows;
    }
    public function execute ($params=null) {
        if ($params == null){
            $params = $this->values;
        }
        $params = array_values($params);
        $elapsed = microtime(true);
        $result = $this->stmt->execute($params, $this->timeout);
        if ( $result !== true){
            $elapsed = round(microtime(true) - $elapsed, 2);
            throw new CoPDOException('query timeout', $elapsed, $this->timeout);
        }
        return $result;
    }

    // 由于 comysql statement 只支持返回数组，因此 fetch_style 不生效
    public function fetch ($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        if ($this->stmt !== null){
            return $this->stmt->fetch();
        }
        else{
            return array_shift($this->rows);
        }
    }

    public function fetchAll ($fetch_style = null, $fetch_argument = null,  $ctor_args = array()) {
        if ($this->stmt !== null){
            return $this->stmt->fetchAll();
        }
        else{
            $rows = $this->rows;
            $this->rows = null;
            return $rows;
        }
    }

}