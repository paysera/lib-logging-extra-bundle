<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Listener;

use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Sentry\ClientInterface;
use Sentry\FlushableClientInterface;

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
        $this->sentryClient->flush();
    }
}
