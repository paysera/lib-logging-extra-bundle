<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Listener;

use Paysera\LoggingExtraBundle\Listener\IterationEndListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use PHPUnit\Framework\TestCase;
use Sentry\ClientInterface;

class IterationEndListenerTest extends TestCase
{
    public function testAfterIterationIncrementsCorrelationIdAndResetsParentCorrelationId(): void
    {
        $correlationIdProvider = new CorrelationIdProvider('test');
        $parentCorrelationIdProvider = new ParentCorrelationIdProvider();
        $parentCorrelationIdProvider->setParentCorrelationId('parent-id-123');

        $listener = new IterationEndListener($correlationIdProvider, $parentCorrelationIdProvider);

        $correlationIdBefore = $correlationIdProvider->getCorrelationId();

        $listener->afterIteration();

        $this->assertNotSame($correlationIdBefore, $correlationIdProvider->getCorrelationId());
        $this->assertNull($parentCorrelationIdProvider->getParentCorrelationId());
    }

    public function testAfterIterationFlushesSentryClient(): void
    {
        $correlationIdProvider = new CorrelationIdProvider('test');
        $parentCorrelationIdProvider = new ParentCorrelationIdProvider();

        $sentryClient = $this->createMock(ClientInterface::class);
        $sentryClient->expects($this->once())->method('flush');

        $listener = new IterationEndListener($correlationIdProvider, $parentCorrelationIdProvider, $sentryClient);

        $listener->afterIteration();
    }

    public function testAfterIterationWorksWithoutSentryClient(): void
    {
        $correlationIdProvider = new CorrelationIdProvider('test');
        $parentCorrelationIdProvider = new ParentCorrelationIdProvider();

        $listener = new IterationEndListener($correlationIdProvider, $parentCorrelationIdProvider);

        $listener->afterIteration();

        $this->assertNull($parentCorrelationIdProvider->getParentCorrelationId());
    }
}
