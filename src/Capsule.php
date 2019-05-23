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

class Capsule extends Manager{
    public static $instance;
    public static function init($app=null){
        if ($app === null){
            // container init
            $app = new Container();
            // bind config
            $app->singleton('config', function () {
                $config = new ConfigRepository;
                $confPath = alias('@root/config/database.php');
                $config->set('database', require $confPath);
                return $config;
            });
            // bind connection factory
            $app->singleton('db.factory', function ($app) {
                return new ConnectionFactory($app);
            });
            // bind database manager
            $app->singleton('db', function ($app) {
                return new DatabaseManager($app, $app['db.factory']);
            });
            // bind connection
            $app->bind('db.connection', function ($app) {
                return $app['db']->connection();
            });
            $app->bind('db.connector.comysql', function ($app, $params){
                return new CoMysqlConnector;
            });
            Connection::resolverFor('comysql', function ($connection, $database, $prefix, $config){
                return new CoMySqlConnection($connection, $database, $prefix, $config);
            });
        }
        // create instance and bind to static class
        $instance = new static($app);
        $instance->setAsGlobal();
        // bind database connection manager
        $instance->manager = $app['db'];
        // bind Eloquent connection manager
        Eloquent::setConnectionResolver($instance->getDatabaseManager());
        if ($dispatcher = $instance->getEventDispatcher()) {
            Eloquent::setEventDispatcher($dispatcher);
        }
    }
    public function __construct(Container $container = null) {
        $this->setupContainer($container ?: new Container);
        // set default pdo fetch mode
        $container['config']['database.fetch'] = PDO::FETCH_OBJ;
        // set default connection
        if ($container['config']['database.default'] == null){
            $container['config']['database.default'] = 'default';
        }
    }


    public static function getApp(){
        return self::$instance->container;
    }
}