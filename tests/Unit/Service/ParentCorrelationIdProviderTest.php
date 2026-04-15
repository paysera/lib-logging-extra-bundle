<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use PHPUnit\Framework\TestCase;

class ParentCorrelationIdProviderTest extends TestCase
{
    public function testReturnsNullByDefault(): void
    {
        $provider = new ParentCorrelationIdProvider();

        $this->assertNull($provider->getParentCorrelationId());
    }

    public function testSetAndGet(): void
    {
        $provider = new ParentCorrelationIdProvider();

        $provider->setParentCorrelationId('abc-123');

        $this->assertSame('abc-123', $provider->getParentCorrelationId());
    }

    public function testResetParentCorrelationId(): void
    {
        $provider = new ParentCorrelationIdProvider();

        $provider->setParentCorrelationId('abc-123');
        $provider->resetParentCorrelationId();

        $this->assertNull($provider->getParentCorrelationId());
    }
}
