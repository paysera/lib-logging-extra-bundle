<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\CorrelationIdHttpClientDecorator;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CorrelationIdHttpClientDecoratorTest extends TestCase
{
    private $decorator;
    private $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $requestStack = $this->createMock(RequestStack::class);

        $correlationIdProvider = $this->createMock(CorrelationIdProvider::class);
        $correlationIdProvider->method('getCorrelationId')->willReturn('mock-correlation-id');

        $this->decorator = new CorrelationIdHttpClientDecorator($correlationIdProvider, $this->httpClient);
    }

    public function testSendRequestAddsCustomHeader(): void
    {
        $url = 'https://api.paysera.com';
        $options = ['headers' => ['Authorization' => 'Bearer mock-token']];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo($url),
                $this->equalTo(
                    [
                        'headers' => [
                            'Authorization' => 'Bearer mock-token',
                            'Paysera-Correlation-Id' => 'mock-correlation-id',
                        ],
                    ]
                )
            )
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->decorator->request('GET', $url, $options);
    }

    public function testSendRequestAddsCustomHeaderIfHeadersNotExists(): void
    {
        $url = 'https://api.paysera.com';

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo($url),
                $this->equalTo(
                    ['headers' => ['Paysera-Correlation-Id' => 'mock-correlation-id']]
                )
            )
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->decorator->request('GET', $url, []);
    }
}
