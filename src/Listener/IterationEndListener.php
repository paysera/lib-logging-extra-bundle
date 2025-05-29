<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\CorrelationIdProvider;
use Sentry\ClientInterface;

/**
 * Intended for cases where the same process is reused for separate job or request processing, like in PHPPM.
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
        $this->sentryClient = $sentryClient instanceof ClientInterface ? $sentryClient : null;
    }

    public function afterIteration()
    {
        $this->correlationIdProvider->reset();
        if ($this->sentryClient !== null) {
            $this->sentryClient->flush();
        }
    }
}
