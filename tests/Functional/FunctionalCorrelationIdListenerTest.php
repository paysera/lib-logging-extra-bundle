<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;
use Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

class FunctionalCorrelationIdListenerTest extends FunctionalTestCase
{
    /**
     * @var CorrelationIdProvider
     */
    private $correlationIdProvider;

    protected function setUp()
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->correlationIdProvider = $container->get('public_correlation_id_provider');
    }

    public function testResponseHeaders()
    {
        $response = $this->makeGetRequest('/index');
        $this->assertTrue($response->headers->has(CorrelationIdListener::HEADER_NAME));
        $this->assertEquals(
            $response->headers->get(CorrelationIdListener::HEADER_NAME),
            $this->correlationIdProvider->getCorrelationId()
        );
    }
}
