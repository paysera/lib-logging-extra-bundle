<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorrelationIdListener
{
    public const HEADER_NAME = 'Paysera-Correlation-Id';

    public function __construct(private CorrelationIdProvider $correlationIdProvider)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $event->getResponse()->headers->set(
            self::HEADER_NAME,
            $this->correlationIdProvider->getCorrelationId()
        );
    }
}
