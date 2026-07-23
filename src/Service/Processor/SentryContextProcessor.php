<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;

if (class_exists('Monolog\LogRecord')) {
    // Monolog v3+ - has LogRecord class with typed ProcessorInterface
    class SentryContextProcessor implements ProcessorInterface
    {
        public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
        {
            $recordArray = $record->toArray();
            $recordArray['context']['extra'] = ($recordArray['context']['extra'] ?? []) + $recordArray['extra'] + $recordArray['context'];
            if (isset($recordArray['extra']['correlation_id'])) {
                $recordArray['context']['tags']['correlation_id'] = $recordArray['extra']['correlation_id'];
            }
            if (isset($recordArray['extra']['trace_id'])) {
                $recordArray['context']['tags']['trace_id'] = $recordArray['extra']['trace_id'];
            }
            unset($recordArray['context']['extra']['tags']);
            unset($recordArray['context']['extra']['exception']);

            return new \Monolog\LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $recordArray['context'],
                $record->extra,
                $record->formatted
            );
        }
    }
} else {
    // Monolog v1/v2 - uses array with untyped ProcessorInterface
    class SentryContextProcessor implements ProcessorInterface
    {
        /**
         * @param array $record
         * @return array
         */
        public function __invoke($record)
        {
            $record['context']['extra'] = ($record['context']['extra'] ?? []) + $record['extra'] + $record['context'];
            if (isset($record['extra']['correlation_id'])) {
                $record['context']['tags']['correlation_id'] = $record['extra']['correlation_id'];
            }
            if (isset($record['extra']['trace_id'])) {
                $record['context']['tags']['trace_id'] = $record['extra']['trace_id'];
            }
            unset($record['context']['extra']['tags']);
            unset($record['context']['extra']['exception']);
            return $record;
        }
    }
}
