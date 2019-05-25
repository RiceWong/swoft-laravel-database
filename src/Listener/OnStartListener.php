<?php


namespace SwoftLaravel\Database\Listener;

use Swoft\Bean\Annotation\ServerListener;
use Swoft\Bootstrap\Listeners\Interfaces\StartInterface;
use Swoft\Bootstrap\SwooleEvent;
use SwoftLaravel\Database\Capsule;
use Swoole\Coroutine\Channel;
use Swoole\Server;
use SwoftLaravel\Database\DatabaseServiceProvider;

/**
 * Class TestStartListener
 * @package App\Boot\Listener
 * @ServerListener(event=SwooleEvent::ON_START)
 */
class OnStartListener implements StartInterface {
    /**
     * @var Server $server
     * */
    public function onStart(Server $server) {
    }
}