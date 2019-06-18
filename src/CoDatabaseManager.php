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
    protected static $context = [];

    public function __construct(Container $container = null) {
        parent::__construct($container, $container['db.factory']);
        $container['config']['database.fetch'] = PDO::FETCH_OBJ;
        // set default connection
        if ($container['config']['database.default'] == null) {
            $container['config']['database.default'] = 'default';
        }
        // 初始化连接池配置
        $config = $container['config'];
        $connections = $config['database.connections'];
        $pools = $config['database.pools'];
        // 如果配置了连接池
        if ($pools !== null) {
            foreach ($pools as $database => $poolConfig) {
                if (!array_key_exists($database, $connections)) {
                    continue;
                }
                // 配置别名
                if (is_string($poolConfig)) {
                    $poolConfig = $pools[$poolConfig];
                }
                $poolConfig['database'] = $database;
                $config["database.connections.$database.query_timeout"] = $poolConfig['max_wait_time'];
                $poolConfig['db_manager'] = $this;
                self::$connPool[$database] = $container->make('db.connector.comysql.pool', $poolConfig);
            }
        }
    }

    public function getPool($name) {
        if (array_key_exists($name, self::$connPool)) {
            return self::$connPool[$name];
        }
        return false;
    }

    protected function getCoId() {
        return Coroutine::getuid();
    }

    public static function getCid($cid = null) {
        return $cid === null ? Coroutine::getuid() : $cid;
    }

    protected static function context_get($name, $cid = null) {
        $cid = self::getCid($cid);
        $value = null;
        if (array_key_exists($cid, self::$context) && array_key_exists($name, self::$context[$cid])) {
            $value = self::$context[$cid][$name];
        }
        return $value;
    }

    protected static function context_set($name, $value, $cid = null) {
        $cid = self::getCid($cid);
        if (!array_key_exists($cid, self::$context)) {
            self::$context[$cid] = [];
        }
        self::$context[$cid][$name] = $value;
    }

    protected static function context_unset($name, $cid = null) {
        $cid = self::getCid($cid);
        if (array_key_exists($cid, self::$context)) {
            if ( array_key_exists($name, self::$context[$cid]) ){
                unset(self::$context[$cid][$name]);
            }
            if (count(self::$context[$cid]) == 0) {
                unset(self::$context[$cid]);
            }
        }
    }

    public function makeConnection($name) {
        return parent::makeConnection($name);
    }

    public function getConnection($name) {
        $pool = $this->getPool($name);
        if ($pool !== false) {
            $connection = $pool->acquireConnection();
        }
        else {
            $connection = $this->makeConnection($name);
        }
        return $connection;
    }

    public function releaseConnection($name, $connection, $attrs) {
        // 单例模式下，如果已经释放了连接，则不重复释放
        if ( $attrs['singleton'] ){
            if (self::context_get($name) == null){
                return;
            }
        }
        // 释放连接
        $pool = $this->getPool($name);
        if ($pool !== false) {
            $pool->releaseConnection($connection);
        }
        else {
            $connection->close();
        }
        // 如果是单例模式，则清除单例连接上下文
        if ( $attrs['singleton'] ){
            self::context_unset($name);
        }
    }

    // 每个coroutine一个连接
    public function connection($name = null, $singleton=true) {
        [$database, $type] = $this->parseConnectionName($name);
        $name = $name ?: $database;
        // 从上下文连接池中获取
        if ($singleton ){
            $connection = self::context_get($name);
            if ($connection == null){
                $connection = $this->getConnection($name, $singleton);
                self::context_set($name, $connection);
            }
        }
        else{
            $connection = $this->getConnection($name, $singleton);
        }
        $attrs = [
            'singleton' => $singleton
        ];
        return new CoMySqlConnectionProxy($connection, $name, $this, $attrs);
    }
    /**
     * Prepare the database connection instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $type
     * @return \Illuminate\Database\Connection
     */
    protected function configure(Connection $connection, $type) {
        $connection = $this->setPdoForType($connection, $type);

        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        $connection->setReconnector(function ($connection) {
            $connection->reconnect();
        });

        return $connection;
    }

    public function disconnect($name = null) {
        $name = $name ?: $this->getDefaultConnection();
        $this->getConnection($name)->disconnect();
    }
}