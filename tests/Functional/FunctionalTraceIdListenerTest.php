<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Paysera\LoggingExtraBundle\Listener\TraceIdListener;
use Paysera\LoggingExtraBundle\Service\TraceIdProvider;

class FunctionalTraceIdListenerTest extends FunctionalTestCase
{
    /**
     * @var TraceIdProvider
     */
    private $traceIdProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->traceIdProvider = $container->get('public_trace_id_provider');
    }

    public function testCapturesTraceIdFromRequestHeader(): void
    {
        $this->handleRequest($this->createRequest('GET', '/index', null, [
            TraceIdListener::HEADER_NAME => 'gateway-trace-id-123',
        ]));

        $this->assertSame('gateway-trace-id-123', $this->traceIdProvider->getTraceId());
    }

    public function testLeavesTraceIdUnsetWhenHeaderAbsent(): void
    {
        $this->handleRequest($this->createRequest('GET', '/index'));

        $this->assertNull($this->traceIdProvider->getTraceId());
    }

    public function testIgnoresInvalidTraceIdHeader(): void
    {
        $this->handleRequest($this->createRequest('GET', '/index', null, [
            TraceIdListener::HEADER_NAME => 'invalid trace id',
        ]));

        $this->assertNull($this->traceIdProvider->getTraceId());
    }
}
