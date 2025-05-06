<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

class CorrelationIdProvider
{
    private $correlationId;
    private $increment;
    private $correlationIdFromHeaderExtractor;
    private $fetchFromHeader;

    public function __construct(
        string $systemName,
        CorrelationIdFromHeaderExtractor $correlationIdFromHeaderExtractor,
        bool $fetchFromHeader
    ) {
        $this->correlationId = uniqid($systemName, true);
        $this->increment = 0;
        $this->correlationIdFromHeaderExtractor = $correlationIdFromHeaderExtractor;
        $this->fetchFromHeader = $fetchFromHeader;
    }

    public function getCorrelationId(): string
    {
        if ($this->fetchFromHeader) {
            $correlationIdFromHeader = $this->correlationIdFromHeaderExtractor->getCorrelationId();
            if ($correlationIdFromHeader !== null) {
                return $correlationIdFromHeader;
            }
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
