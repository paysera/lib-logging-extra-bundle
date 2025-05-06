<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdFromHeaderExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CorrelationIdFromHeaderExtractorTest extends TestCase
{
    private $requestStack;

    private $provider;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->provider = new CorrelationIdFromHeaderExtractor($this->requestStack);
    }

    public function testGetHeaderReturnsNullWhenNoRequestInStack(): void
    {
        $this->assertNull(
            $this->provider->getCorrelationId()
        );
    }

    public function testGetHeaderReturnsNullWhenNoCorrelationIdHeaderInRequest(): void
    {
        $this->requestStack->push(new Request());

        $this->assertNull(
            $this->provider->getCorrelationId()
        );
    }

    public function testGetHeaderReturnsValueWhenHeaderIsSet(): void
    {
        $correlationId = 'test-correlation-id';
        $request = new Request();
        $request->headers->set(CorrelationIdListener::HEADER_NAME, $correlationId);
        $this->requestStack->push($request);

        $this->assertEquals(
            $correlationId,
            $this->provider->getCorrelationId()
        );
    }
}
