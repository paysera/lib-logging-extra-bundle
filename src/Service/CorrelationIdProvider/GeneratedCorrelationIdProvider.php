<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

/**
 * @internal
 */
class GeneratedCorrelationIdProvider
{
    private $correlationId;
    private $systemName;
    private $increment;

    public function __construct(string $systemName)
    {
        $this->systemName = $systemName;
        $this->correlationId = null;
        $this->increment = 0;
        $this->correlationId = uniqid($this->systemName, true);
    }

    public function getCorrelationId(): string
    {
        if ($this->increment === 0) {
            return $this->correlationId;
        }

        return $this->buildIncrementedCorrelationId();
    }

    private function buildIncrementedCorrelationId(): string
    {
        return sprintf('%s_%s', $this->correlationId, $this->increment);
    }

    /**
     * Changes correlation_id while still maintaining same prefix to be able to find relations in
     * cases of bugs happening due to shared state between different processing cycles
     */
    public function increment(): void
    {
        if ($this->increment === PHP_INT_MAX) {
            $this->increment = 0;
        }

        $this->increment++;
    }
}
