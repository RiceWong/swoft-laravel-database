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

class Capsule  {
    protected static $instance;
    protected $manager;
    public function __construct(Container $container = null) {
        $this->manager = new CoDatabaseManager($container);
    }
    public function setAsGlobal(){
        self::$instance = $this;
    }
    public static function __callStatic($method, $parameters) {
        return self::$instance->manager->$method(...$parameters);
    }
    public static function getDatabaseManager() {
        return self::$instance->manager;
    }

    public static function collectResource(){
        if (self::$instance !== null){
            self::$instance->clearContextConnection();
        }
    }
}