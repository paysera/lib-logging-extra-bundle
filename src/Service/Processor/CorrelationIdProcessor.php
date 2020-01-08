<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

class CorrelationIdProcessor implements ProcessorInterface
{
    private $correlationIdProvider;

    public function __construct(CorrelationIdProvider $correlationIdProvider)
    {
        $this->correlationIdProvider = $correlationIdProvider;
    }

    public function __invoke(array $record)
    {
        $record['extra']['correlation_id'] = $this->correlationIdProvider->getCorrelationId();
        return $record;
    }
}
