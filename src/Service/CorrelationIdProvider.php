<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

class CorrelationIdProvider
{
    private $correlationId;
    private $increment;

    public function __construct(string $systemName)
    {
        $this->correlationId = uniqid($systemName, true);
        $this->increment = 0;
    }

    public function getCorrelationId(): string
    {
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
