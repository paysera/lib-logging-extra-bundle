<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Support;

/**
 * Builds Monolog records and reads their fields in a version-agnostic way
 * (a LogRecord on Monolog v3, an associative array on v1/v2), so processor and
 * formatter tests do not each repeat the same v1/v3 compatibility scaffolding.
 */
trait MonologRecordTrait
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return \Monolog\LogRecord|array<string, mixed>
     */
    private function buildLogRecord(array $overrides = [])
    {
        $datetime = $overrides['datetime'] ?? new \DateTimeImmutable();
        $channel = $overrides['channel'] ?? 'test';
        $level = $overrides['level'] ?? 200;
        $levelName = $overrides['level_name'] ?? 'INFO';
        $message = $overrides['message'] ?? 'test message';
        $context = $overrides['context'] ?? [];
        $extra = $overrides['extra'] ?? [];

        if (class_exists('Monolog\LogRecord')) {
            return new \Monolog\LogRecord(
                $datetime,
                $channel,
                \Monolog\Level::from((int) $level),
                $message,
                $context,
                $extra,
            );
        }

        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $channel,
            'datetime' => $datetime,
            'extra' => $extra,
        ];
    }

    /**
     * @param \Monolog\LogRecord|array<string, mixed> $record
     *
     * @return mixed
     */
    private function getRecordField($record, string $key)
    {
        if ($record instanceof \Monolog\LogRecord) {
            return $record->{$key};
        }

        return $record[$key];
    }

    /**
     * @param \Monolog\LogRecord|array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private function getRecordExtra($record): array
    {
        if ($record instanceof \Monolog\LogRecord) {
            return $record->extra;
        }

        return $record['extra'];
    }
}
