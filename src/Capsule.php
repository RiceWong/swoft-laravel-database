<?php


namespace SwoftLaravel\Database;


use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Capsule{
    public static $instance;
    public static function init(){
        self::getInstance();
    }
    public static function getInstance(){
        if  (self::$instance == null) {
            $app = new Container();
            $app->singleton('config', function () {
                $config = new ConfigRepository;
                $confPath = alias('@root/config/database.php');
                $config->set('database', require $confPath);
                return $config;
            });
            $instance = new Manager($app);
            $instance->setAsGlobal();
            $instance->bootEloquent();
            self::$instance = $instance;
        }
        return self::$instance;
    }
    public static function connection($connection = null) {
        return self::getInstance()->getConnection($connection);
    }
    public static function __callStatic($method, $parameters) {
        return static::connection()->$method(...$parameters);
    }
}