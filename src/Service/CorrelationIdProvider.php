<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

class CorrelationIdProvider
{
    private $correlationId;
    private $increment;
    private $correlationIdFromHeaderExtractor;

    public function __construct(string $systemName, CorrelationIdFromHeaderExtractor $correlationIdFromHeaderExtractor)
    {
        $this->correlationId = uniqid($systemName, true);
        $this->increment = 0;
        $this->correlationIdFromHeaderExtractor = $correlationIdFromHeaderExtractor;
    }

    public function getCorrelationId(): string
    {
        $correlationIdFromHeader = $this->correlationIdFromHeaderExtractor->getCorrelationId();
        if ($correlationIdFromHeader !== null) {
            return $correlationIdFromHeader;
        }

        if ($this->increment === 0) {
            return $this->correlationId;
        }

        return sprintf('%s_%s', $this->correlationId, $this->increment);
    }

    public function incrementIdentifier()
    {
        $this->increment++;
    }
}
