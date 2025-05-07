<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider\CorrelationIdProvider;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CorrelationIdHttpClientDecorator implements HttpClientInterface
{
    use DecoratorTrait;

    private $correlationIdProvider;

    public function __construct(
        CorrelationIdProvider $correlationIdProvider,
        ?HttpClientInterface $client = null
    ) {
        $this->client = $client ?? HttpClient::create();
        $this->correlationIdProvider = $correlationIdProvider;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options['headers'] = $options['headers'] ?? [];
        $options['headers'][CorrelationIdListener::HEADER_NAME] = $this->correlationIdProvider->getCorrelationId();

        return $this->client->request($method, $url, $options);
    }
}
