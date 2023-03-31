<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

class FunctionalCorrelationIdListenerTest extends FunctionalTestCase
{
    private CorrelationIdProvider $correlationIdProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->correlationIdProvider = $container->get('public_correlation_id_provider');
    }

    public function testResponseHeaders(): void
    {
        $response = $this->makeGetRequest('/index');
        static::assertTrue($response->headers->has(CorrelationIdListener::HEADER_NAME));
        static::assertEquals(
            $response->headers->get(CorrelationIdListener::HEADER_NAME),
            $this->correlationIdProvider->getCorrelationId()
        );
    }
}
