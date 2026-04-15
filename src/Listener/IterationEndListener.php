<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
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
    private ?ClientInterface $sentryClient;

    public function __construct(
        CorrelationIdProvider $correlationIdProvider,
        ParentCorrelationIdProvider $parentCorrelationIdProvider,
        ?ClientInterface $sentryClient = null
    ) {
        $this->correlationIdProvider = $correlationIdProvider;
        $this->parentCorrelationIdProvider = $parentCorrelationIdProvider;
        $this->sentryClient = $sentryClient;
    }

    public function afterIteration(): void
    {
        $this->correlationIdProvider->incrementIdentifier();
        $this->parentCorrelationIdProvider->resetParentCorrelationId();
        if ($this->sentryClient !== null) {
            $this->sentryClient->flush();
        }
    }
}
