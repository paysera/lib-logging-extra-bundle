<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\CorrelationIdProvider;

use PHPUnit\Framework\TestCase;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\GeneratedCorrelationIdProvider;

class GeneratedCorrelationIdProviderTest extends TestCase
{
    /**
     * @var string
     */
    private $systemName;

    /**
     * @var GeneratedCorrelationIdProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->systemName = 'test-system';
        $this->provider = new GeneratedCorrelationIdProvider($this->systemName);
    }

    public function testGetCorrelationId(): void
    {
        // Act
        $correlationId = $this->provider->getCorrelationId();

        // Assert
        $this->assertNotEmpty($correlationId);
        $this->assertStringStartsWith($this->systemName, $correlationId);
    }

    public function testGetCorrelationIdReturnsSameValueBeforeIncrement(): void
    {
        // Act
        $firstCall = $this->provider->getCorrelationId();
        $secondCall = $this->provider->getCorrelationId();

        // Assert
        $this->assertSame($firstCall, $secondCall);
    }

    public function testIncrementChangesCorrelationId(): void
    {
        // Arrange
        $initialCorrelationId = $this->provider->getCorrelationId();

        // Act
        $this->provider->increment();
        $incrementedCorrelationId = $this->provider->getCorrelationId();

        // Assert
        $this->assertNotEquals($initialCorrelationId, $incrementedCorrelationId);
        $this->assertStringStartsWith($initialCorrelationId . '_', $incrementedCorrelationId);
        $this->assertEquals($initialCorrelationId . '_1', $incrementedCorrelationId);
    }

    public function testMultipleIncrementsAppendIncrementCounter(): void
    {
        // Arrange
        $initialCorrelationId = $this->provider->getCorrelationId();

        // Act - increment multiple times
        $this->provider->increment();
        $firstIncrementId = $this->provider->getCorrelationId();
        
        $this->provider->increment();
        $secondIncrementId = $this->provider->getCorrelationId();
        
        $this->provider->increment();
        $thirdIncrementId = $this->provider->getCorrelationId();

        // Assert
        $this->assertEquals($initialCorrelationId . '_1', $firstIncrementId);
        $this->assertEquals($initialCorrelationId . '_2', $secondIncrementId);
        $this->assertEquals($initialCorrelationId . '_3', $thirdIncrementId);
    }

    public function testCorrelationIdFormatFollowsExpectedPattern(): void
    {
        // Act
        $correlationId = $this->provider->getCorrelationId();

        // Assert
        // The format should be: systemName + uniqid (which includes a timestamp and microseconds)
        $this->assertRegExp(
            sprintf('/^test\-system[0-9a-f]{14}\.[0-9a-f]+$/', preg_quote($this->systemName, '/')),
            $correlationId
        );
    }
}
