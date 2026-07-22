<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use Sentry\ClientInterface;

/**
 * Intended for cases where the same process is reused for separate job or request processing, like in PHP-FPM.
 *
 * Changes correlation_id while still maintaining same prefix to be able to find relations in cases of bugs happening
 * due to shared state between different processing cycles
 */
class IterationEndListener
{
    private CorrelationIdProvider $correlationIdProvider;
    private ParentCorrelationIdProvider $parentCorrelationIdProvider;
    private TraceIdProvider $traceIdProvider;
    private ?ClientInterface $sentryClient;

    public function __construct(
        CorrelationIdProvider $correlationIdProvider,
        ParentCorrelationIdProvider $parentCorrelationIdProvider,
        TraceIdProvider $traceIdProvider,
        ?ClientInterface $sentryClient = null
    ) {
        $this->correlationIdProvider = $correlationIdProvider;
        $this->parentCorrelationIdProvider = $parentCorrelationIdProvider;
        $this->traceIdProvider = $traceIdProvider;
        $this->sentryClient = $sentryClient;
    }

    public function afterIteration(): void
    {
        $this->correlationIdProvider->incrementIdentifier();
        $this->parentCorrelationIdProvider->resetParentCorrelationId();
        $this->traceIdProvider->resetTraceId();
        if ($this->sentryClient !== null) {
            $this->sentryClient->flush();
        }
    }
}
