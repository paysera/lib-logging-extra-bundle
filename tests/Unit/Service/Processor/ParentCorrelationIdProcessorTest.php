<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\Processor\ParentCorrelationIdProcessor;
use PHPUnit\Framework\TestCase;

class ParentCorrelationIdProcessorTest extends TestCase
{
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

        $this->assertSame('parent-id-123', $this->getExtra($record, 'parent_correlation_id'));
    }

    public function testDoesNotAddKeyWhenNull(): void
    {
        $record = $this->invokeProcessor();

        $this->assertArrayNotHasKey('parent_correlation_id', $this->getAllExtra($record));
    }

    /**
     * @return \Monolog\LogRecord|array
     */
    private function invokeProcessor()
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

        return ($this->processor)($record);
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
