<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use InvalidArgumentException;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Formats Monolog records as compact JSON Lines for stdout, collected by VictoriaLogs.
 *
 * Monolog v3 delivers records as {@see LogRecord} objects while v1/v2 use arrays, so the class is
 * declared twice with the matching {@see self::format()} signature. Both delegate the JSON encoding
 * to a composed {@see StdoutRecordEncoder}.
 */
if (class_exists('Monolog\LogRecord')) {
    class StdoutJsonFormatter extends NormalizerFormatter
    {
        use NormalizeCompatibilityTrait;

        /**
         * @var StdoutRecordEncoder
         */
        private $encoder;

        public function __construct(string $applicationName)
        {
            parent::__construct('Y-m-d\TH:i:s.uP');

            $this->encoder = new StdoutRecordEncoder($applicationName);
        }

        public function format(LogRecord $record): string
        {
            return $this->encoder->encode(
                $record->datetime,
                $record->channel,
                $record->level->value,
                $record->level->getName(),
                (string) $record->message,
                (array) $this->normalize($record->context),
                (array) $this->normalize($record->extra)
            );
        }

        public function formatBatch(array $records): string
        {
            $formatted = '';
            foreach ($records as $record) {
                $formatted .= $this->format($record);
            }

            return $formatted;
        }
    }
} else {
    class StdoutJsonFormatter extends NormalizerFormatter
    {
        use NormalizeCompatibilityTrait;

        /**
         * @var StdoutRecordEncoder
         */
        private $encoder;

        public function __construct(string $applicationName)
        {
            parent::__construct('Y-m-d\TH:i:s.uP');

            $this->encoder = new StdoutRecordEncoder($applicationName);
        }

        /**
         * @param array<string, mixed> $record
         */
        public function format(array $record): string
        {
            if (!isset($record['datetime']) || !$record['datetime'] instanceof \DateTimeInterface) {
                throw new InvalidArgumentException('The record must contain a "datetime" DateTimeInterface value.');
            }

            return $this->encoder->encode(
                $record['datetime'],
                (string) ($record['channel'] ?? ''),
                (int) ($record['level'] ?? Logger::DEBUG),
                (string) ($record['level_name'] ?? ''),
                (string) ($record['message'] ?? ''),
                (array) $this->normalize($record['context'] ?? []),
                (array) $this->normalize($record['extra'] ?? [])
            );
        }

        /**
         * @param array<array-key, mixed> $records
         */
        public function formatBatch(array $records): string
        {
            $formatted = '';
            foreach ($records as $record) {
                if (is_array($record)) {
                    $formatted .= $this->format($record);
                }
            }

            return $formatted;
        }
    }
}
