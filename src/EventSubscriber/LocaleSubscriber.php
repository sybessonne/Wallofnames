<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $languagesAvailables;

    public function __construct(array $languagesAvailables)
    {
        $this->languagesAvailables = $languagesAvailables;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $locale = $request->getPreferredLanguage($this->languagesAvailables);
            $request->setLocale($locale);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => [['onKernelRequest', 20]],
        ];
    }
}
