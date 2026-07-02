<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use DateTimeInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 * Base for the stdout JSON-Lines formatter collected by VictoriaLogs.
 *
 * Produces one compact JSON object per line, byte-compatible with the canonical
 * StdoutJsonFormatter shipped by evp/lib-application-logging-bundle: the same field set and
 * order, the same syslog severity mapping, the same 32766-byte cap and shrink order. The
 * concrete class (see StdoutJsonFormatter.php) only adapts the Monolog record shape
 * (array on Monolog 1/2, LogRecord on Monolog 3).
 */
abstract class AbstractStdoutJsonFormatter extends NormalizerFormatter
{
    use NormalizeCompatibilityTrait;

    public const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
    public const MAX_JSON_BYTE_COUNT = 32766;

    /**
     * Monolog level (100..600) to syslog/GELF severity (7..0) — the same mapping the Graylog
     * pipeline uses: DEBUG=7, INFO=6, NOTICE=5, WARNING=4, ERROR=3, CRITICAL=2, ALERT=1, EMERGENCY=0.
     *
     * @var array<int, int>
     */
    private const SYSLOG_LEVELS_BY_MONOLOG_LEVEL = [
        Logger::DEBUG => 7,
        Logger::INFO => 6,
        Logger::NOTICE => 5,
        Logger::WARNING => 4,
        Logger::ERROR => 3,
        Logger::CRITICAL => 2,
        Logger::ALERT => 1,
        Logger::EMERGENCY => 0,
    ];

    /**
     * @var string
     */
    private $applicationName;

    public function __construct(string $applicationName)
    {
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->applicationName = $applicationName;
    }

    /**
     * @param array<array-key, mixed> $records
     */
    public function formatBatch(array $records): string
    {
        $formatted = '';
        foreach ($records as $record) {
            $formatted .= $this->format($record);
        }

        return $formatted;
    }

    /**
     * Builds one JSON line from the version-agnostic parts of a Monolog record.
     *
     * @param array<array-key, mixed> $context
     * @param array<array-key, mixed> $extra
     */
    protected function formatRecord(
        DateTimeInterface $datetime,
        string $channel,
        int $level,
        string $levelName,
        string $message,
        array $context,
        array $extra
    ): string {
        $normalizedContext = $this->normalize($context);
        $normalizedExtra = $this->normalize($extra);

        $correlationId = null;
        if (is_array($normalizedExtra) && array_key_exists('correlation_id', $normalizedExtra)) {
            $correlationId = $normalizedExtra['correlation_id'];
            unset($normalizedExtra['correlation_id']);
        }

        $fields = [
            'timestamp' => $datetime->format('Y-m-d\TH:i:s.uP'),
            'application_name' => $this->applicationName,
            'channel' => $channel,
            'level' => $this->getSyslogLevel($level),
            'level_name' => $levelName,
            'message' => $message,
            'full_message' => null,
            'context' => is_array($normalizedContext) ? $normalizedContext : [],
            'extra' => is_array($normalizedExtra) ? $normalizedExtra : [],
            'correlation_id' => $correlationId,
        ];

        return $this->encodeWithinByteLimit($fields) . "\n";
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function encodeWithinByteLimit(array $fields): string
    {
        $json = $this->encode($fields);
        if (strlen($json) <= self::MAX_JSON_BYTE_COUNT) {
            return $json;
        }

        $fields['truncated'] = true;
        if (isset($fields['full_message'])) {
            unset($fields['full_message']);
            $json = $this->encode($fields);
            if (strlen($json) <= self::MAX_JSON_BYTE_COUNT) {
                return $json;
            }
        }

        unset($fields['context'], $fields['extra']);
        $json = $this->encode($fields);
        if (strlen($json) <= self::MAX_JSON_BYTE_COUNT) {
            return $json;
        }

        // Budget against the ENCODED length: removing N raw bytes of message removes at least N
        // encoded bytes (JSON escaping only inflates), so a single pass never overshoots the cap.
        $overflow = strlen($json) - self::MAX_JSON_BYTE_COUNT;
        $fields['message'] = $this->truncateToByteLength(
            $fields['message'],
            strlen($fields['message']) - $overflow
        );

        return $this->encode($fields);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function encode(array $fields): string
    {
        return (string) json_encode($this->filterEmptyFields($fields), self::JSON_FLAGS);
    }

    private function truncateToByteLength(string $string, int $maxByteLength): string
    {
        if ($maxByteLength <= 0) {
            return '';
        }

        if (strlen($string) <= $maxByteLength) {
            return $string;
        }

        $truncated = substr($string, 0, $maxByteLength);

        // Drop a trailing incomplete UTF-8 sequence so the value stays valid for json_encode.
        $index = strlen($truncated);
        while ($index > 0 && (ord($truncated[$index - 1]) & 0xC0) === 0x80) {
            --$index;
        }

        if ($index === 0) {
            return '';
        }

        $leadByte = ord($truncated[$index - 1]);
        if ($leadByte < 0xC0) {
            return $truncated;
        }

        if ($leadByte >= 0xF0) {
            $expectedLength = 4;
        } elseif ($leadByte >= 0xE0) {
            $expectedLength = 3;
        } else {
            $expectedLength = 2;
        }

        if (strlen($truncated) - ($index - 1) < $expectedLength) {
            return substr($truncated, 0, $index - 1);
        }

        return $truncated;
    }

    private function getSyslogLevel(int $level): int
    {
        return self::SYSLOG_LEVELS_BY_MONOLOG_LEVEL[$level] ?? $level;
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function filterEmptyFields(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if ($value === null || $value === []) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}
