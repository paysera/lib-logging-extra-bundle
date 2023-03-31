<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

class CorrelationIdProcessor implements ProcessorInterface
{
    public function __construct(private CorrelationIdProvider $correlationIdProvider)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record['extra']['correlation_id'] = $this->correlationIdProvider->getCorrelationId();

        return $record;
    }
}
