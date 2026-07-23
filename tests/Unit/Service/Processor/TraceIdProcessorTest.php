<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\Processor\TraceIdProcessor;
use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use Paysera\LoggingExtraBundle\Tests\Unit\Support\MonologRecordTrait;
use PHPUnit\Framework\TestCase;

class TraceIdProcessorTest extends TestCase
{
    use MonologRecordTrait;

    private TraceIdProvider $provider;
    private TraceIdProcessor $processor;

    protected function setUp(): void
    {
        $this->provider = new TraceIdProvider();
        $this->processor = new TraceIdProcessor($this->provider);
    }

    public function testAddsTraceIdWhenSet(): void
    {
        $this->provider->setTraceId('trace-id-123');

        $record = $this->invokeProcessor();

        $this->assertSame('trace-id-123', $this->getRecordExtra($record)['trace_id']);
    }

    public function testDoesNotAddKeyWhenNull(): void
    {
        $record = $this->invokeProcessor();

        $this->assertArrayNotHasKey('trace_id', $this->getRecordExtra($record));
    }

    public function testLeavesTheRestOfTheRecordUntouched(): void
    {
        $this->provider->setTraceId('trace-id-123');

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
