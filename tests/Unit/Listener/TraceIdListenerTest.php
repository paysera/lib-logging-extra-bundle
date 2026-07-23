<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Listener;

use Paysera\LoggingExtraBundle\Listener\TraceIdListener;
use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TraceIdListenerTest extends TestCase
{
    private TraceIdProvider $provider;
    private TraceIdListener $listener;

    protected function setUp(): void
    {
        $this->provider = new TraceIdProvider();
        $this->listener = new TraceIdListener($this->provider);
    }

    /**
     * @dataProvider provideValidHeaders
     */
    public function testSetsTraceIdFromHeader(string $headerValue): void
    {
        $request = new Request();
        $request->headers->set(TraceIdListener::HEADER_NAME, $headerValue);

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertSame($headerValue, $this->provider->getTraceId());
    }

    public function provideValidHeaders(): array
    {
        return [
            'plain id' => ['trace-id-123'],
            'max length' => [str_repeat('a', 200)],
        ];
    }

    public function testResetsStaleValueWhenHeaderAbsent(): void
    {
        $this->provider->setTraceId('stale-id');

        $request = new Request();

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertNull($this->provider->getTraceId());
    }

    /**
     * @dataProvider provideInvalidHeaders
     */
    public function testResetsStaleValueWhenHeaderIsInvalid(string $headerValue): void
    {
        $this->provider->setTraceId('stale-id');

        $request = new Request();
        $request->headers->set(TraceIdListener::HEADER_NAME, $headerValue);

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertNull($this->provider->getTraceId());
    }

    public function testOverwritesStaleValueWithValidHeader(): void
    {
        $this->provider->setTraceId('stale-id');

        $request = new Request();
        $request->headers->set(TraceIdListener::HEADER_NAME, 'fresh-id-456');

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertSame('fresh-id-456', $this->provider->getTraceId());
    }

    public function provideInvalidHeaders(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 201)],
            'space' => ['trace id 123'],
            'newline injection' => ["trace-id\ninjected=value"],
            'tab' => ["trace-id\t123"],
            'structural punctuation' => ['trace-id{"key":"value"}'],
            'slash' => ['trace/id/123'],
        ];
    }

    public function testIgnoresSubRequests(): void
    {
        $this->provider->setTraceId('existing-id');

        $request = new Request();
        $request->headers->set(TraceIdListener::HEADER_NAME, 'sub-request-id');

        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('existing-id', $this->provider->getTraceId());
    }

    private function createRequestEvent(Request $request, int $requestType): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }

    private function mainRequestType(): int
    {
        return defined(HttpKernelInterface::class . '::MAIN_REQUEST')
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::MASTER_REQUEST;
    }
}
