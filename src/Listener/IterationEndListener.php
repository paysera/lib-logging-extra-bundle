<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Sentry\ClientInterface;
use Sentry\FlushableClientInterface;

/**
 * Intended for cases where the same process is reused for separate job or request processing, like in PHPPM.
 *
 * Changes correlation_id while still maintaining same prefix to be able to find relations in cases of bugs happening
 * due to shared state between different processing cycles
 */
class IterationEndListener
{
    private $correlationIdProvider;
    private $sentryClient;

    public function __construct(
        CorrelationIdProvider $correlationIdProvider,
        ClientInterface $sentryClient = null
    ) {
        $this->correlationIdProvider = $correlationIdProvider;
        $this->sentryClient = $sentryClient instanceof FlushableClientInterface ? $sentryClient : null;
    }

    public function afterIteration()
    {
        $this->correlationIdProvider->incrementIdentifier();
        if ($this->sentryClient !== null) {
            $this->sentryClient->flush();
        }
    }
}
