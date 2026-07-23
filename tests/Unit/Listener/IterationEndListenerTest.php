<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Listener;

use Paysera\LoggingExtraBundle\Listener\IterationEndListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use PHPUnit\Framework\TestCase;
use Sentry\ClientInterface;

class IterationEndListenerTest extends TestCase
{
    public function testAfterIterationIncrementsCorrelationIdAndResetsParentCorrelationAndTraceIds(): void
    {
        $correlationIdProvider = new CorrelationIdProvider('test');
        $parentCorrelationIdProvider = new ParentCorrelationIdProvider();
        $parentCorrelationIdProvider->setParentCorrelationId('parent-id-123');
        $traceIdProvider = new TraceIdProvider();
        $traceIdProvider->setTraceId('trace-id-123');

        $listener = new IterationEndListener(
            $correlationIdProvider,
            $parentCorrelationIdProvider,
            null,
            $traceIdProvider
        );

        $correlationIdBefore = $correlationIdProvider->getCorrelationId();

        $listener->afterIteration();

        $this->assertNotSame($correlationIdBefore, $correlationIdProvider->getCorrelationId());
        $this->assertNull($parentCorrelationIdProvider->getParentCorrelationId());
        $this->assertNull($traceIdProvider->getTraceId());
    }

    /**
     * @dataProvider provideTraceIdProviderArgument
     */
    public function testAfterIterationFlushesSentryClient(bool $withTraceIdProvider): void
    {
        $correlationIdProvider = new CorrelationIdProvider('test');
        $parentCorrelationIdProvider = new ParentCorrelationIdProvider();
        $parentCorrelationIdProvider->setParentCorrelationId('parent-id-123');

        $sentryClient = $this->createMock(ClientInterface::class);
        $sentryClient->expects($this->once())->method('flush');

        $listener = new IterationEndListener(
            $correlationIdProvider,
            $parentCorrelationIdProvider,
            $sentryClient,
            $withTraceIdProvider ? new TraceIdProvider() : null
        );

        $listener->afterIteration();

        $this->assertNull($parentCorrelationIdProvider->getParentCorrelationId());
    }

    public function provideTraceIdProviderArgument(): array
    {
        return [
            'current signature' => [true],
            'pre-3.5.0 signature without trace id provider' => [false],
        ];
    }
}
