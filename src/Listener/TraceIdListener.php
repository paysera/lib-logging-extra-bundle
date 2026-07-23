<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use Symfony\Component\HttpKernel\Event\GetResponseEvent as LegacyRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

if (class_exists(RequestEvent::class)) {
    class_alias(RequestEvent::class, 'Paysera\LoggingExtraBundle\Listener\TraceIdRequestEventAlias');
} else {
    class_alias(LegacyRequestEvent::class, 'Paysera\LoggingExtraBundle\Listener\TraceIdRequestEventAlias');
}

/**
 * Captures the request-spanning trace id from the `Paysera-Trace-Id` header the public
 * gateway stamps on every inbound request, so every service records the same `trace_id`
 * without writing any code of its own.
 */
class TraceIdListener
{
    public const HEADER_NAME = 'Paysera-Trace-Id';

    private TraceIdProvider $traceIdProvider;

    public function __construct(TraceIdProvider $traceIdProvider)
    {
        $this->traceIdProvider = $traceIdProvider;
    }

    public function onKernelRequest(TraceIdRequestEventAlias $event): void
    {
        $mainRequestType = defined(HttpKernelInterface::class . '::MAIN_REQUEST')
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::MASTER_REQUEST;

        if ($mainRequestType !== $event->getRequestType()) {
            return;
        }

        // Reset first so a value captured on an earlier request handled by the same
        // reused process (e.g. RoadRunner, PHP-FPM) cannot leak into this one.
        $this->traceIdProvider->resetTraceId();

        $headerValue = $event->getRequest()->headers->get(self::HEADER_NAME);

        if ($headerValue !== null) {
            $this->traceIdProvider->setTraceId($headerValue);
        }
    }
}
