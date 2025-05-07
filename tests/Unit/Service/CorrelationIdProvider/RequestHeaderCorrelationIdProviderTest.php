<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\CorrelationIdProvider;

use PHPUnit\Framework\TestCase;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\RequestHeaderCorrelationIdProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;

class RequestHeaderCorrelationIdProviderTest extends TestCase
{
    private $requestStack;
    private $provider;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->requestStack = new RequestStack();
        $this->provider = new RequestHeaderCorrelationIdProvider($this->requestStack);
    }
    
    public function testGetCorrelationIdFromRequestHeader(): void
    {
        // Arrange
        $correlationId = 'test-correlation-id';
        $request = new Request();
        $request->headers->set(CorrelationIdListener::HEADER_NAME, $correlationId);
        $this->requestStack->push($request);
        
        // Act
        $result = $this->provider->getCorrelationId();
        
        // Assert
        $this->assertEquals($correlationId, $result);
    }
    
    public function testGetCorrelationIdReturnsCachedValue(): void
    {
        // Arrange
        $correlationId = 'test-correlation-id';
        $request = new Request();
        $request->headers->set(CorrelationIdListener::HEADER_NAME, $correlationId);
        $this->requestStack->push($request);
        
        // First call to cache the value
        $this->provider->getCorrelationId();
        
        // Modify the request header
        $request->headers->set(CorrelationIdListener::HEADER_NAME, 'modified-correlation-id');
        
        // Act
        $result = $this->provider->getCorrelationId();
        
        // Assert - should return the cached value, not the modified one
        $this->assertEquals($correlationId, $result);
    }
    
    public function testResetClearsCorrelationIdCache(): void
    {
        // Arrange
        $correlationId = 'test-correlation-id';
        $request = new Request();
        $request->headers->set(CorrelationIdListener::HEADER_NAME, $correlationId);
        $this->requestStack->push($request);
        
        // Cache the initial value
        $this->provider->getCorrelationId();
        
        // Change the header value
        $newCorrelationId = 'new-correlation-id';
        $request->headers->set(CorrelationIdListener::HEADER_NAME, $newCorrelationId);
        
        // Act
        $this->provider->reset();
        $result = $this->provider->getCorrelationId();
        
        // Assert
        $this->assertEquals($newCorrelationId, $result);
    }
    
    public function testGetCorrelationIdReturnsNullWhenNoRequest(): void
    {
        // No request in the stack
        // Act
        $result = $this->provider->getCorrelationId();
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetCorrelationIdReturnsNullWhenNoHeader(): void
    {
        // Arrange - request without the correlation ID header
        $request = new Request();
        $this->requestStack->push($request);
        
        // Act
        $result = $this->provider->getCorrelationId();
        
        // Assert
        $this->assertNull($result);
    }
}
