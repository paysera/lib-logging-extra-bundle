<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;

class SentryContextProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        $record['context']['extra'] = ($record['context']['extra'] ?? []) + $record['extra'] + $record['context'];
        if (isset($record['extra']['correlation_id'])) {
            $record['context']['tags']['correlation_id'] = $record['extra']['correlation_id'];
        }
        unset($record['context']['extra']['tags']);
        unset($record['context']['extra']['exception']);
        return $record;
    }
}
