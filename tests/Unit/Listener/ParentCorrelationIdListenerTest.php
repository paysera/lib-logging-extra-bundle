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

    public function testSetsParentCorrelationIdFromHeader(): void
    {
        $request = new Request();
        $request->headers->set('Paysera-Correlation-Id', 'parent-id-123');

        $mainRequestType = defined(HttpKernelInterface::class . '::MAIN_REQUEST')
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::MASTER_REQUEST;

        $event = $this->createRequestEvent($request, $mainRequestType);

        $this->listener->onKernelRequest($event);

        $this->assertSame('parent-id-123', $this->provider->getParentCorrelationId());
    }

    public function testDoesNotSetWhenHeaderAbsent(): void
    {
        $this->provider->setParentCorrelationId('existing-id');

        $request = new Request();

        $mainRequestType = defined(HttpKernelInterface::class . '::MAIN_REQUEST')
            ? HttpKernelInterface::MAIN_REQUEST
            : HttpKernelInterface::MASTER_REQUEST;

        $event = $this->createRequestEvent($request, $mainRequestType);

        $this->listener->onKernelRequest($event);

        $this->assertSame('existing-id', $this->provider->getParentCorrelationId());
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
}
