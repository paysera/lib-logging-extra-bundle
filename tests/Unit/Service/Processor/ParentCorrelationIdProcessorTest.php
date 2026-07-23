<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\Processor\ParentCorrelationIdProcessor;
use Paysera\LoggingExtraBundle\Tests\Unit\Support\MonologRecordTrait;
use PHPUnit\Framework\TestCase;

class ParentCorrelationIdProcessorTest extends TestCase
{
    use MonologRecordTrait;

    private ParentCorrelationIdProvider $provider;
    private ParentCorrelationIdProcessor $processor;

    protected function setUp(): void
    {
        $this->provider = new ParentCorrelationIdProvider();
        $this->processor = new ParentCorrelationIdProcessor($this->provider);
    }

    public function testAddsParentCorrelationIdWhenSet(): void
    {
        $this->provider->setParentCorrelationId('parent-id-123');

        $record = $this->invokeProcessor();

        $this->assertSame('parent-id-123', $this->getRecordExtra($record)['parent_corr_id']);
    }

    public function testDoesNotAddKeyWhenNull(): void
    {
        $record = $this->invokeProcessor();

        $this->assertArrayNotHasKey('parent_corr_id', $this->getRecordExtra($record));
    }

    /**
     * @return \Monolog\LogRecord|array<string, mixed>
     */
    private function invokeProcessor()
    {
        return ($this->processor)($this->buildLogRecord());
    }
}
