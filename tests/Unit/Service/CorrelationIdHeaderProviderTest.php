<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\CorrelationIdFromHeaderExtractor;
use PHPUnit\Framework\TestCase;

class CorrelationIdHeaderProviderTest extends TestCase
{
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new CorrelationIdFromHeaderExtractor();
    }

    public function testGetHeaderReturnsNullWhenHeaderIsNotSet(): void
    {
        // Arrange: Ensure $_SERVER is empty
        unset($_SERVER['HTTP_PAYSERA_CORRELATION_ID']);

        // Act: Call the method
        $result = $this->provider->getCorrelationId();

        // Assert: It should return null
        $this->assertNull($result);
    }

    public function testGetHeaderReturnsValueWhenHeaderIsSet(): void
    {
        // Arrange: Set the expected header in $_SERVER
        $_SERVER['HTTP_PAYSERA_CORRELATION_ID'] = 'test-correlation-id';

        // Act: Call the method
        $result = $this->provider->getCorrelationId();

        // Assert: It should return the correct value
        $this->assertSame('test-correlation-id', $result);
    }
}
