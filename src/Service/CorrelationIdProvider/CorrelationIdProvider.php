<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

/**
 * @internal
 */
class CorrelationIdProvider
{
    private $generatedCorrelationIdProvider;
    private $requestHeaderCorrelationIdProvider;
    private $fetchFromRequest;
    private $isFetchedFromRequest;

    public function __construct(
        GeneratedCorrelationIdProvider $generatedCorrelationIdProvider,
        RequestHeaderCorrelationIdProvider $requestHeaderCorrelationIdProvider,
        bool $fetchFromRequest
    ) {
        $this->generatedCorrelationIdProvider = $generatedCorrelationIdProvider;
        $this->requestHeaderCorrelationIdProvider = $requestHeaderCorrelationIdProvider;
        $this->fetchFromRequest = $fetchFromRequest;
        $this->isFetchedFromRequest = false;
    }

    public function getCorrelationId(): string
    {
        if ($this->fetchFromRequest) {
            $correlationIdFromRequest = $this->requestHeaderCorrelationIdProvider->getCorrelationId();

            if ($correlationIdFromRequest !== null) {
                $this->isFetchedFromRequest = true;

                return $correlationIdFromRequest;
            }
        }

        return $this->generatedCorrelationIdProvider->getCorrelationId();
    }

    public function reset(): void
    {
        if ($this->isFetchedFromRequest) {
            $this->requestHeaderCorrelationIdProvider->reset();
            $this->isFetchedFromRequest = false;

            return;
        }

        $this->generatedCorrelationIdProvider->increment();
    }
}
