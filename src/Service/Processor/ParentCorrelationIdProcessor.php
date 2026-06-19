<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;

if (class_exists('Monolog\LogRecord')) {
    class ParentCorrelationIdProcessor implements ProcessorInterface
    {
        private $parentCorrelationIdProvider;

        public function __construct(ParentCorrelationIdProvider $parentCorrelationIdProvider)
        {
            $this->parentCorrelationIdProvider = $parentCorrelationIdProvider;
        }

        public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
        {
            $parentCorrelationId = $this->parentCorrelationIdProvider->getParentCorrelationId();

            if ($parentCorrelationId === null) {
                return $record;
            }

            return new \Monolog\LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $record->context,
                array_merge($record->extra, ['parent_corr_id' => $parentCorrelationId]),
                $record->formatted
            );
        }
    }
} else {
    class ParentCorrelationIdProcessor implements ProcessorInterface
    {
        private $parentCorrelationIdProvider;

        public function __construct(ParentCorrelationIdProvider $parentCorrelationIdProvider)
        {
            $this->parentCorrelationIdProvider = $parentCorrelationIdProvider;
        }

        /**
         * @param array $record
         * @return array
         */
        public function __invoke($record)
        {
            $parentCorrelationId = $this->parentCorrelationIdProvider->getParentCorrelationId();

            if ($parentCorrelationId === null) {
                return $record;
            }

            $record['extra']['parent_corr_id'] = $parentCorrelationId;
            return $record;
        }
    }
}
