<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\CorrelationIdFromHeaderExtractor;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use PHPUnit\Framework\TestCase;

class CorrelationIdProviderTest extends TestCase
{
    private $correlationIdProvider;

    private $correlationIdFromHeaderExtractor;

    protected function setUp(): void
    {
        $this->correlationIdFromHeaderExtractor = $this->createMock(CorrelationIdFromHeaderExtractor::class);
        $this->correlationIdProvider = new CorrelationIdProvider(
            'test-system',
            $this->correlationIdFromHeaderExtractor,
            false
        );
    }

    public function testInitialCorrelationId(): void
    {
        $uniqueId = $this->correlationIdProvider->getCorrelationId();
        $this->assertStringStartsWith(
            'test-system',
            $uniqueId,
            'Initial correlation ID did not start with the system name'
        );
    }

    public function testGetCorrelationIdWithoutRequest(): void
    {
        $this->correlationIdFromHeaderExtractor->method('getCorrelationId')->willReturn(null);

        $correlationId = $this->correlationIdProvider->getCorrelationId();
        $this->assertStringStartsWith('test-system', $correlationId);
    }

    public function testGetCorrelationIdFromRequestHeader(): void
    {
        $correlationIdProvider = new CorrelationIdProvider(
            'test-system',
            $this->correlationIdFromHeaderExtractor,
            true
        );

        $this->correlationIdFromHeaderExtractor->method('getCorrelationId')->willReturn('test-correlation-id');

        $correlationId = $correlationIdProvider->getCorrelationId();
        $this->assertEquals(
            'test-correlation-id',
            $correlationId,
            'Correlation ID did not match the value from the header'
        );
    }

    public function testIncrementedCorrelationId(): void
    {
        $this->correlationIdFromHeaderExtractor->method('getCorrelationId')->willReturn(null);

        $initialCorrelationId = $this->correlationIdProvider->getCorrelationId();

        $this->correlationIdProvider->incrementIdentifier();
        $incrementedCorrelationId = $this->correlationIdProvider->getCorrelationId();

        $this->assertNotEquals($initialCorrelationId, $incrementedCorrelationId);
        $this->assertStringEndsWith('_1', $incrementedCorrelationId, 'Incremented correlation ID did not end with _1');

        $this->correlationIdProvider->incrementIdentifier();
        $secondIncrementedCorrelationId = $this->correlationIdProvider->getCorrelationId();

        $this->assertStringEndsWith(
            '_2',
            $secondIncrementedCorrelationId,
            'Correlation ID after second increment did not end with _2'
        );
    }
}
