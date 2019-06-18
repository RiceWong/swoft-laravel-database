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
    public $debug=false;
    public $tpl = "%.4f|co[%3d]|pool[%s]:%8s, stats: [%3d/%3d], usage: [%3d/%3d], waiting: %3d";
    public $tpl_fields = ['timestamp', 'cid',  'database',  'action', 'current', 'capacity', 'used',  'current', 'waiting'];
    public function __construct($app, $config) {
        $this->database        = $config['database'];
        $this->max_connection  = $config['max_connection'];
        $this->min_connection  = $config['min_connection'];
        $this->cur_connection  = 0;
        $this->timeout         = $config['max_wait_time'] /1000;
        $this->chan            = new Channel($config['max_connection']);
        $this->manager         = $config['db_manager'];
        // 处理调试参数
        $debug                 = data_get($config, 'debug', false);
        if ($debug !== false){
            // 解析数组格式
            if ( is_array($debug) ){
                $debugInfo = array_merge([
                    'enable'      => false,
                    'tpl'        => $this->tpl,
                    'tpl_fields' => $this->tpl_fields
                ], $debug);
                $debug = $debugInfo['enable'];
                $this->tpl = $debugInfo['tpl'];
                $this->tpl_fields = $debugInfo['tpl_fields'];
            }
            $this->debug = $debug;
        }
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
        $this->debug && $this->dump('acquire');
        if ( !$this->hasConnections() ){
            if ( !$this->reachMaxConnection() ){
                $connection = $this->makeConnection($this->database);
                $this->addConnection($connection);
            }
        }
        $connection = $this->chan->pop();
        $this->debug && $this->dump('acquired');
        return $connection;
    }
    public function releaseConnection($connection){
        $this->chan->push($connection);
        $this->debug && $this->dump('release');
    }
    public function closeConnection($connection){
        $connection->close();
        $this->cur_connections--;
    }
    public function stat($fields=['cid', 'timestamp', 'database', 'current', 'capacity', 'waiting', 'used']){
        $database  = $this->database;
        $capacity  = $this->max_connection;
        $current   = $this->cur_connection;
        $chanStats = $this->chan->stats();
        $waiting   = $chanStats['consumer_num'];
        $used      = $current - $chanStats['queue_num'];
        $cid       = \Swoole\Coroutine::getuid();
        $timestamp = microtime(true);
        return compact($fields);
    }
    public function dump($action){
        $stats = $this->stat();
        $stats['action'] = $action;
        $tplVars = [];
        foreach ($this->tpl_fields as $field){
            $tplVars[] = $stats[$field];
        }
        echo sprintf($this->tpl, ...$tplVars)."\n";
    }
}