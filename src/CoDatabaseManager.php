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

class CoDatabaseManager extends DatabaseManager {
    protected static $contextConnections = [];
    public function __construct(Container $container = null) {
        parent::__construct($container, $container['db.factory']);
        $container['config']['database.fetch'] = PDO::FETCH_OBJ;
        // set default connection
        if ($container['config']['database.default'] == null){
            $container['config']['database.default'] = 'default';
        }
    }
    public function getConnection($name) {
        $cid = Coroutine::getCid();
        if (!array_key_exists($cid, self::$contextConnections)){
            self::$contextConnections[$cid] = [];
        }
        if ( array_key_exists($name, self::$contextConnections[$cid])){
            return self::$contextConnections[$cid][$name];
        }
        return null;
    }
    public function setConnection($connection, $name){
        $cid = Coroutine::getCid();
        self::$contextConnections[$cid][$name] = $connection;
        return $connection;
    }

    // 每个coroutine一个连接
    public function connection($name = null) {
        [$database, $type] = $this->parseConnectionName($name);
        $name = $name ?: $database;
        // 从上下文连接池中获取
        $connection = $this->getConnection($name);
        if ( $connection === null) {
            $connection = $this->setConnection($this->configure(
                $this->makeConnection($database), $type
            ), $name);
        }
        $cid = Coroutine::getCid();
        $count = count(self::$contextConnections[$cid]);
        return $connection;
    }
    public function clearContextConnection(){
        $cid = Coroutine::getCid();
        if (!self::$contextConnections[$cid]){
            return;
        }
        if (count(self::$contextConnections[$cid]) > 0){
            foreach (self::$contextConnections[$cid] as $connection){
                $connection->disconnect();
            }
            unset(self::$contextConnections[$cid]);
        }
    }
    public function disconnect($name = null) {
        $name = $name ?: $this->getDefaultConnection();
        $this->getConnection($name)->disconnect();
    }
}