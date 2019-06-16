<?php


namespace SwoftLaravel\Database;
use Swoole\Coroutine\Channel;
class CoConnectionPool {
    public $database;
    public $max_connection;
    public $min_connection;
    public $cur_connection;
    public $timeout;
    public $chan;
    public $manager;
    public function __construct($app, $config) {
        $this->database        = $config['database'];
        $this->max_connection  = $config['max_connection'];
        $this->min_connection  = $config['min_connection'];
        $this->cur_connection  = 0;
        $this->timeout         = $config['max_wait_time'] /1000;
        $this->chan            = new Channel($config['max_connection']);
        $this->manager         = $config['db_manager'];
        if ($this->min_connection > 0){
            $count = $this->min_connection;
            while($count-- > 0){
                $connection = $this->makeConnection($this->database);
                $this->addConnection($connection);
            }
        }
    }
    public function reachMaxConnection(){
        return $this->cur_connection >= $this->max_connection;
    }
    public function hasConnections(){
        return !$this->chan->isEmpty();
    }

    public function makeConnection($database){
        return  $this->manager->makeConnection($database);
    }
    // 向连接池中添加连接
    public function addConnection($connection){
        $this->cur_connection++;
        $this->chan->push($connection);
    }
    public function acquireConnection(){
        if ( !$this->hasConnections() ){
            if ( !$this->reachMaxConnection() ){
                $connection = $this->makeConnection($this->database);
                $this->addConnection($connection);
            }
        }
        return $this->chan->pop();
    }
    public function releaseConnection($connection){
        $this->chan->push($connection);
    }
    public function closeConnection($connection){
        $connection->close();
        $thios->cur_connections--;
    }
    public function dump(){
        $name = $this->database;
        $max = $this->max_connection;
        $cur = $this->cur_connection;
        echo sprintf("name: %16s, capbility: [%3d/%3d]\n", $name, $cur, $max);
    }
}