<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent as LegacyResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

if (class_exists(LegacyResponseEvent::class)) {
    class CorrelationIdListener
    {
        const HEADER_NAME = 'Paysera-Correlation-Id';

        private $correlationIdProvider;

        public function __construct(CorrelationIdProvider $correlationIdProvider)
        {
            $this->correlationIdProvider = $correlationIdProvider;
        }

        public function onKernelResponse(LegacyResponseEvent $event)
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
} else {
    class CorrelationIdListener
    {
        public const HEADER_NAME = 'Paysera-Correlation-Id';
        private $correlationIdProvider;

        public function __construct(CorrelationIdProvider $correlationIdProvider)
        {
            $this->correlationIdProvider = $correlationIdProvider;
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
}
