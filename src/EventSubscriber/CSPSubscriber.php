<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use App\Utils\NonceGenerator;

class CSPSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'kernel.response' => 'addCSPHeaderToResponse'
        ];
    }

    public function addCSPHeaderToResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        //$response->headers->set("Content-Security-Policy", $response->headers->get("Content-Security-Policy")."; require-trusted-types-for 'script'");
        $response->headers->set("Vary", "Origin");
        $response->headers->set("Feature-Policy", 'none');
    }
}
