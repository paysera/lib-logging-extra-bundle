<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Sentry\ClientInterface;

/**
 * Intended for cases where the same process is reused for separate job or request processing, like in PHPPM.
 *
 * Changes correlation_id while still maintaining same prefix to be able to find relations in cases of bugs happening
 * due to shared state between different processing cycles
 */
class IterationEndListener
{
    private ?ClientInterface $sentryClient;

    public function __construct(
        private CorrelationIdProvider $correlationIdProvider,
        ClientInterface $sentryClient = null
    ) {
        $this->sentryClient = $sentryClient instanceof ClientInterface ? $sentryClient : null;
    }

    public function afterIteration(): void
    {
        $this->correlationIdProvider->incrementIdentifier();
        $this->sentryClient?->flush();
    }
}
