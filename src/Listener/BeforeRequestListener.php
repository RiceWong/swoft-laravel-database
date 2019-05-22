<?php

namespace SwoftLaravel\Database\Listener;

use Swoft\App;
use Swoft\Bean\Annotation\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\Http\Server\Event\HttpServerEvent;
use SwoftLaravel\Database\Capsule;
/**
 * Class TestStartListener
 * @package App\Boot\Listener
 * @Listener(HttpServerEvent::BEFORE_REQUEST)
 *
 */
class BeforeRequestListener implements EventHandlerInterface {
    public function handle(EventInterface $event){
        Capsule::init();
    }
}