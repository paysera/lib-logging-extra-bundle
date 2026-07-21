<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\Processor\TraceIdProcessor;
use Paysera\LoggingExtraBundle\Service\TraceIdProviderInterface;
use Paysera\LoggingExtraBundle\Tests\Unit\Support\MonologRecordTrait;
use PHPUnit\Framework\TestCase;

class TraceIdProcessorTest extends TestCase
{
    use MonologRecordTrait;

    private TraceIdProviderInterface $provider;
    private TraceIdProcessor $processor;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(TraceIdProviderInterface::class);
        $this->processor = new TraceIdProcessor($this->provider);
    }

    public function testAddsTraceIdWhenSet(): void
    {
        $this->provider->method('getTraceId')->willReturn('trace-id-123');

        $record = $this->invokeProcessor();

        $this->assertSame('trace-id-123', $this->getRecordExtra($record)['trace_id']);
    }

    public function testDoesNotAddKeyWhenNull(): void
    {
        $this->provider->method('getTraceId')->willReturn(null);

        $record = $this->invokeProcessor();

        $this->assertArrayNotHasKey('trace_id', $this->getRecordExtra($record));
    }

    public function testLeavesTheRestOfTheRecordUntouched(): void
    {
        $this->provider->method('getTraceId')->willReturn('trace-id-123');

        $record = $this->invokeProcessor();

        $this->assertSame('test message', $this->getRecordField($record, 'message'));
        $this->assertSame('test', $this->getRecordField($record, 'channel'));
        $this->assertSame('kept', $this->getRecordExtra($record)['existing']);
    }

    /**
     * @return \Monolog\LogRecord|array<string, mixed>
     */
    private function invokeProcessor()
    {
        return ($this->processor)($this->buildLogRecord(['extra' => ['existing' => 'kept']]));
    }
}
