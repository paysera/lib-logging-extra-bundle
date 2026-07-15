<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\Processor\TraceIdProcessor;
use Paysera\LoggingExtraBundle\Service\TraceIdProviderInterface;
use PHPUnit\Framework\TestCase;

class TraceIdProcessorTest extends TestCase
{
    private TraceIdProviderInterface $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(TraceIdProviderInterface::class);
    }

    public function testAddsTraceIdWhenSet(): void
    {
        $this->provider->method('getTraceId')->willReturn('trace-id-123');
        $processor = new TraceIdProcessor($this->provider);

        $record = $this->invokeProcessor($processor);

        $this->assertSame('trace-id-123', $this->getExtra($record, 'trace_id'));
    }

    public function testDoesNotAddKeyWhenNull(): void
    {
        $this->provider->method('getTraceId')->willReturn(null);
        $processor = new TraceIdProcessor($this->provider);

        $record = $this->invokeProcessor($processor);

        $this->assertArrayNotHasKey('trace_id', $this->getAllExtra($record));
    }

    /**
     * @return \Monolog\LogRecord|array
     */
    private function invokeProcessor(TraceIdProcessor $processor)
    {
        if (class_exists('Monolog\LogRecord')) {
            $record = new \Monolog\LogRecord(
                new \DateTimeImmutable(),
                'test',
                \Monolog\Level::Info,
                'test message',
                [],
                [],
            );
        } else {
            $record = [
                'message' => 'test message',
                'context' => [],
                'level' => 200,
                'level_name' => 'INFO',
                'channel' => 'test',
                'datetime' => new \DateTimeImmutable(),
                'extra' => [],
            ];
        }

        return ($processor)($record);
    }

    /**
     * @param \Monolog\LogRecord|array $record
     */
    private function getExtra($record, string $key): string
    {
        if ($record instanceof \Monolog\LogRecord) {
            return $record->extra[$key];
        }

        return $record['extra'][$key];
    }

    /**
     * @param \Monolog\LogRecord|array $record
     */
    private function getAllExtra($record): array
    {
        if ($record instanceof \Monolog\LogRecord) {
            return $record->extra;
        }

        return $record['extra'];
    }
}
