<?php


namespace SwoftLaravel\Database;


use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\MySqlConnection;
use PDO;
use Swoole\Coroutine;
class DatabaseServiceProvider {
    protected static $registered = false;
    public static function init($confPath, $app=null){
        if (self::$registered){
            return;
        }
        if ($app === null){
            // container init
            $app = new Container();
            // bind config
            $app->singleton('config', function () use ($confPath){
                $config = new ConfigRepository;
                $config->set('database', require $confPath);
                return $config;
            });

            // bind connection factory
            $app->singleton('db.factory', function ($app) {
                return new ConnectionFactory($app);
            });
            // bind database manager
            $app->singleton('db', function ($app) {
                return new CoDatabaseManager($app);
            });
            // bind connection
            $app->bind('db.connection', function ($app) {
                return $app['db']->connection();
            });
            // register comysql connector
            $app->bind('db.connector.comysql', function ($app, $params){
                return new CoMysqlConnector($params);
            });
            $app->bind('db.connector.comysql.pool', function ($app, $params){
                return new CoConnectionPool($app, $params);
            });
            // register comysql driver
            Connection::resolverFor('comysql', function ($connection, $database, $prefix, $config){
                return new CoMySqlConnection($connection, $database, $prefix, $config);
            });
        }
        // create instance and bind to static class
        $instance = new Capsule($app);
        $instance->setAsGlobal();
        Eloquent::setConnectionResolver($app['db']);
        if ($app->bound('events')) {
            Eloquent::setEventDispatcher($app['events']);
        }
        self::$registered = true;
    }
}