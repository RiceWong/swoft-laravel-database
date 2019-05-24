<?php

namespace  SwoftLaravel\Database\Listener;

use Swoft\App;
use Swoft\Core\RequestContext;
use Swoft\Event\AppEvent;
use Swoft\Http\Server\Event\HttpServerEvent;
use SwoftLaravel\Database\Capsule;
use Swoole\Server;
use Swoft\Bean\Annotation\SwooleListener;
use Swoft\Bean\Annotation\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;

/**
 * Class TestStartListener
 * @package App\Boot\Listener
 * @Listener(AppEvent::RESOURCE_RELEASE)
 *
 */
class ResourceReleaseListener implements EventHandlerInterface {
    public function handle(EventInterface $event){
        Capsule::connection()->close();
    }
}