<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\CorrelationIdProvider;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\CorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\GeneratedCorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\RequestHeaderCorrelationIdProvider;

class CorrelationIdProviderTest extends TestCase
{
    /**
     * @var GeneratedCorrelationIdProvider&MockObject
     */
    private $generatedProvider;

    /**
     * @var RequestHeaderCorrelationIdProvider&MockObject
     */
    private $requestHeaderProvider;

    /**
     * @var string
     */
    private $defaultGeneratedId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->defaultGeneratedId = 'generated-correlation-id';
        
        $this->generatedProvider = $this->createMock(GeneratedCorrelationIdProvider::class);
        $this->generatedProvider
            ->method('getCorrelationId')
            ->willReturn($this->defaultGeneratedId);
            
        $this->requestHeaderProvider = $this->createMock(RequestHeaderCorrelationIdProvider::class);
    }

    public function testReturnsCorrelationIdFromRequestWhenAvailable(): void
    {
        // Arrange
        $requestCorrelationId = 'request-correlation-id';
        $this->requestHeaderProvider
            ->method('getCorrelationId')
            ->willReturn($requestCorrelationId);
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            true
        );
        
        // Act
        $actualCorrelationId = $correlationIdProvider->getCorrelationId();
        
        // Assert
        $this->assertEquals($requestCorrelationId, $actualCorrelationId);
    }
    
    public function testFallsBackToGeneratedIdWhenNoCorrelationIdInRequest(): void
    {
        // Arrange
        $this->requestHeaderProvider
            ->method('getCorrelationId')
            ->willReturn(null);
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            true
        );
        
        // Act
        $actualCorrelationId = $correlationIdProvider->getCorrelationId();
        
        // Assert
        $this->assertEquals($this->defaultGeneratedId, $actualCorrelationId);
    }
    
    public function testAlwaysUsesGeneratedIdWhenFetchFromRequestIsFalse(): void
    {
        // Arrange
        $requestCorrelationId = 'request-correlation-id';
        $this->requestHeaderProvider
            ->method('getCorrelationId')
            ->willReturn($requestCorrelationId);
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            false
        );
        
        // Act
        $actualCorrelationId = $correlationIdProvider->getCorrelationId();
        
        // Assert
        $this->assertEquals($this->defaultGeneratedId, $actualCorrelationId);
    }
    
    public function testResetClearsRequestProviderWhenIdWasFetchedFromRequest(): void
    {
        // Arrange
        $requestCorrelationId = 'request-correlation-id';
        $this->requestHeaderProvider
            ->method('getCorrelationId')
            ->willReturn($requestCorrelationId);
            
        $this->requestHeaderProvider
            ->expects($this->once())
            ->method('reset');
            
        $this->generatedProvider
            ->expects($this->never())
            ->method('increment');
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            true
        );
        
        // First get the correlation ID to set isFetchedFromRequest = true
        $correlationIdProvider->getCorrelationId();
        
        // Act
        $correlationIdProvider->reset();
    }
    
    public function testResetIncrementsGeneratedProviderWhenIdWasNotFetchedFromRequest(): void
    {
        // Arrange
        $this->requestHeaderProvider
            ->expects($this->never())
            ->method('reset');
            
        $this->generatedProvider
            ->expects($this->once())
            ->method('increment');
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            false
        );
        
        // Act
        $correlationIdProvider->reset();
    }
    
    public function testResetIncrementsGeneratedProviderWhenNoCorrelationIdInRequest(): void
    {
        // Arrange
        $this->requestHeaderProvider
            ->method('getCorrelationId')
            ->willReturn(null);
            
        $this->requestHeaderProvider
            ->expects($this->never())
            ->method('reset');
            
        $this->generatedProvider
            ->expects($this->once())
            ->method('increment');
            
        $correlationIdProvider = new CorrelationIdProvider(
            $this->generatedProvider,
            $this->requestHeaderProvider,
            true
        );
        
        // First get the correlation ID, but it will use generated since request returns null
        $correlationIdProvider->getCorrelationId();
        
        // Act
        $correlationIdProvider->reset();
    }
}
