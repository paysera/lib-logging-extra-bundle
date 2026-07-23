<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Listener;

use Paysera\LoggingExtraBundle\Listener\ParentCorrelationIdListener;
use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ParentCorrelationIdListenerTest extends TestCase
{
    private ParentCorrelationIdProvider $provider;
    private ParentCorrelationIdListener $listener;

    protected function setUp(): void
    {
        $this->provider = new ParentCorrelationIdProvider();
        $this->listener = new ParentCorrelationIdListener($this->provider);
    }

    /**
     * @dataProvider provideValidHeaders
     */
    public function testSetsParentCorrelationIdFromHeader(string $headerValue): void
    {
        $request = new Request();
        $request->headers->set('Paysera-Correlation-Id', $headerValue);

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertSame($headerValue, $this->provider->getParentCorrelationId());
    }

    public function provideValidHeaders(): array
    {
        return [
            'plain id' => ['parent-id-123'],
            'max length' => [str_repeat('a', 128)],
        ];
    }

    public function testResetsStaleValueWhenHeaderAbsent(): void
    {
        $this->provider->setParentCorrelationId('stale-id');

        $request = new Request();

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertNull($this->provider->getParentCorrelationId());
    }

    /**
     * @dataProvider provideInvalidHeaders
     */
    public function testResetsStaleValueWhenHeaderIsInvalid(string $headerValue): void
    {
        $this->provider->setParentCorrelationId('stale-id');

        $request = new Request();
        $request->headers->set('Paysera-Correlation-Id', $headerValue);

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertNull($this->provider->getParentCorrelationId());
    }

    public function testOverwritesStaleValueWithValidHeader(): void
    {
        $this->provider->setParentCorrelationId('stale-id');

        $request = new Request();
        $request->headers->set('Paysera-Correlation-Id', 'fresh-id-456');

        $event = $this->createRequestEvent($request, $this->mainRequestType());

        $this->listener->onKernelRequest($event);

        $this->assertSame('fresh-id-456', $this->provider->getParentCorrelationId());
    }

    public function provideInvalidHeaders(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 129)],
            'space' => ['parent id 123'],
            'newline injection' => ["parent-id\ninjected=value"],
            'tab' => ["parent-id\t123"],
            'structural punctuation' => ['parent-id{"key":"value"}'],
            'slash' => ['parent/id/123'],
        ];
    }

    public function testIgnoresSubRequests(): void
    {
        $this->provider->setParentCorrelationId('existing-id');

        $request = new Request();
        $request->headers->set('Paysera-Correlation-Id', 'sub-request-id');

        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('existing-id', $this->provider->getParentCorrelationId());
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
