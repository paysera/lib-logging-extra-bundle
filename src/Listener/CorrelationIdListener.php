<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorrelationIdListener
{
    const HEADER_NAME = 'Paysera-Correlation-Id';

    private $correlationIdProvider;

    public function __construct(CorrelationIdProvider $correlationIdProvider)
    {
        $this->correlationIdProvider = $correlationIdProvider;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $event->getResponse()->headers->set(
            self::HEADER_NAME,
            $this->correlationIdProvider->getCorrelationId()
        );
    }
}
