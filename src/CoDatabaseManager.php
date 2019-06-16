<?php


namespace SwoftLaravel\Database;


use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\MySqlConnection;
use PDO;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel as chan;
class CoDatabaseManager extends DatabaseManager {
    protected static $connPool = [];
    public function __construct(Container $container = null) {
        parent::__construct($container, $container['db.factory']);
        $container['config']['database.fetch'] = PDO::FETCH_OBJ;
        // set default connection
        if ($container['config']['database.default'] == null){
            $container['config']['database.default'] = 'default';
        }
        // 初始化连接池配置
        $config      = $container['config'];
        $connections = $config['database.connections'];
        $pools       = $config['database.pools'];
        // 如果配置了连接池
        if ( $pools !== null ){
            foreach ($pools as $database=>$poolConfig) {
                if ( !array_key_exists($database, $connections) ){
                    continue;
                }
                // 配置别名
                if (is_string($poolConfig) ){
                    $poolConfig = $pools[$poolConfig];
                }
                $poolConfig['database'] = $database;
                $config["database.connections.$database.query_timeout"] = $poolConfig['max_wait_time'];
                $poolConfig['db_manager'] = $this;
                self::$connPool[$database] = $container->make('db.connector.comysql.pool', $poolConfig);
            }
        }
    }
    public function getPool($name){
        if ( array_key_exists($name, self::$connPool)){
            return self::$connPool[$name];
        }
        return false;
    }

    protected function getCoId(){
        return Coroutine::getuid();
    }

    public function makeConnection($name) {
        return parent::makeConnection($name);
    }

    public function getConnection($name) {
        $pool = $this->getPool($name);
        if ($pool !== false){
            $connection = $pool->acquireConnection();
        }
        else{
            $connection = $this->makeConnection($name);
        }
        return $connection;
    }
    public function releaseConnection($name, $connection){
        $pool = $this->getPool($name);
        if ($pool !== false){
            $pool->releaseConnection($connection);
            $pool->dump();
        }
        else{
            $connection->close();
        }
    }

    // 每个coroutine一个连接
    public function connection($name = null) {
        [$database, $type] = $this->parseConnectionName($name);
        $name = $name ?: $database;
        // 从上下文连接池中获取
        $connection = $this->getConnection($name);
        return new CoMySqlConnectionProxy($connection, $name, $this);
    }

    public function disconnect($name = null) {
        $name = $name ?: $this->getDefaultConnection();
        $this->getConnection($name)->disconnect();
    }
}