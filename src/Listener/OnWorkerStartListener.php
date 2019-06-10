<?php

namespace  SwoftLaravel\Database\Listener;

use Swoft\App;
use Swoft\Bean\Annotation\ServerListener;
use Swoft\Bean\Annotation\Value;
use Swoft\Bootstrap\Listeners\Interfaces\WorkerStartInterface;
use Swoft\Bootstrap\SwooleEvent;
use Swoole\Server;
use SwoftLaravel\Database\DatabaseServiceProvider;
/**
 * @ServerListener(event=SwooleEvent::ON_WORKER_START)
 */
class OnWorkerStartListener implements WorkerStartInterface {
    public function onWorkerStart(Server $server, int $workerId, bool $isWorker) {
        if ($isWorker){
            $confPath = BASE_PATH.'/config/database.php';
            DatabaseServiceProvider::init($confPath);
        }
    }
}
