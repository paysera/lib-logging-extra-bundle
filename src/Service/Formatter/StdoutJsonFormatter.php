<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Compact one-object-per-line JSON (JSON Lines) formatter for stdout, collected by VictoriaLogs.
 *
 * The shape mirrors the Graylog/GELF pipeline (syslog severity, application name, correlation id)
 * so both destinations carry equivalent records. Correlation id is hoisted from `extra`
 * (populated by {@see \Paysera\LoggingExtraBundle\Service\Processor\CorrelationIdProcessor})
 * to a top-level field.
 *
 * Monolog v3 delivers records as {@see \Monolog\LogRecord} objects while v1/v2 use arrays, so the
 * class is declared twice with the matching {@see self::format()} signature; all shared logic lives
 * in {@see AbstractStdoutJsonFormatter}.
 */
if (class_exists('Monolog\LogRecord')) {
    class StdoutJsonFormatter extends AbstractStdoutJsonFormatter
    {
        public function format(LogRecord $record): string
        {
            return $this->formatRecord(
                $record->datetime,
                $record->channel,
                $record->level->value,
                $record->level->getName(),
                (string) $record->message,
                $record->context,
                $record->extra
            );
        }
    }
} else {
    class StdoutJsonFormatter extends AbstractStdoutJsonFormatter
    {
        /**
         * @param array<string, mixed> $record
         */
        public function format(array $record): string
        {
            return $this->formatRecord(
                $record['datetime'],
                (string) ($record['channel'] ?? ''),
                (int) ($record['level'] ?? Logger::DEBUG),
                (string) ($record['level_name'] ?? ''),
                (string) ($record['message'] ?? ''),
                (array) ($record['context'] ?? []),
                (array) ($record['extra'] ?? [])
            );
        }
    }
}
