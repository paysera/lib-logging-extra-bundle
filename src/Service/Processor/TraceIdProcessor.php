<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use Paysera\LoggingExtraBundle\Service\TraceIdProviderInterface;

if (class_exists('Monolog\LogRecord')) {
    // Monolog v3+ - has LogRecord class with typed ProcessorInterface
    class TraceIdProcessor implements ProcessorInterface
    {
        private $traceIdProvider;

        public function __construct(TraceIdProviderInterface $traceIdProvider)
        {
            $this->traceIdProvider = $traceIdProvider;
        }

        public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
        {
            $traceId = $this->traceIdProvider->getTraceId();

            if ($traceId === null) {
                return $record;
            }

            return new \Monolog\LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $record->context,
                array_merge($record->extra, ['trace_id' => $traceId]),
                $record->formatted
            );
        }
    }
} else {
    // Monolog v1/v2 - uses array with untyped ProcessorInterface
    class TraceIdProcessor implements ProcessorInterface
    {
        private $traceIdProvider;

        public function __construct(TraceIdProviderInterface $traceIdProvider)
        {
            $this->traceIdProvider = $traceIdProvider;
        }

        /**
         * @param array $record
         * @return array
         */
        public function __invoke($record)
        {
            $traceId = $this->traceIdProvider->getTraceId();

            if ($traceId === null) {
                return $record;
            }

            $record['extra']['trace_id'] = $traceId;
            return $record;
        }
    }
}
