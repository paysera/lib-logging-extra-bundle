<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use Symfony\Component\HttpKernel\Event\GetResponseEvent as LegacyRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

if (class_exists(RequestEvent::class)) {
    class_alias(RequestEvent::class, 'Paysera\LoggingExtraBundle\Listener\RequestEventAlias');
} else {
    class_alias(LegacyRequestEvent::class, 'Paysera\LoggingExtraBundle\Listener\RequestEventAlias');
}

class ParentCorrelationIdListener
{
    private ParentCorrelationIdProvider $parentCorrelationIdProvider;

    public function __construct(ParentCorrelationIdProvider $parentCorrelationIdProvider)
    {
        $this->parentCorrelationIdProvider = $parentCorrelationIdProvider;
    }

    public function onKernelRequest(RequestEventAlias $event): void
    {
        $mainRequestType = defined(HttpKernelInterface::class . '::MAIN_REQUEST')
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::MASTER_REQUEST;

        if ($mainRequestType !== $event->getRequestType()) {
            return;
        }

        $headerValue = $event->getRequest()->headers->get(CorrelationIdListener::HEADER_NAME);

        $this->parentCorrelationIdProvider->setParentCorrelationId($headerValue);
    }
}
