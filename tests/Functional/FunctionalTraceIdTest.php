<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Gelf\Message;
use Monolog\Handler\FingersCrossedHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestGraylogHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Service\TestTransport;
use Sentry\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FunctionalTraceIdTest extends FunctionalTestCase
{
    private const TRACE_ID = 'test-trace-id';

    /**
     * @var ContainerInterface
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->setUpContainer('basic.yml');
    }

    public function testAddsTraceIdToGraylogWhenSet(): void
    {
        $this->container->get('public_trace_id_provider')->setTraceId(self::TRACE_ID);

        $this->container->get('public_logger')->warning('WARN');

        $additionals = $this->getFirstGraylogMessage()->getAllAdditionals();

        $this->assertArrayHasKey('trace_id', $additionals);
        $this->assertSame(self::TRACE_ID, $additionals['trace_id']);
    }

    public function testPromotesTraceIdToSentryTag(): void
    {
        $this->container->get('public_trace_id_provider')->setTraceId(self::TRACE_ID);

        $this->container->get('public_logger')->error('boom');

        $tags = $this->getFirstSentryEvent()->getTags();

        $this->assertArrayHasKey('trace_id', $tags);
        $this->assertSame(self::TRACE_ID, $tags['trace_id']);
    }

    public function testOmitsTraceIdWhenNotSet(): void
    {
        $this->container->get('public_logger')->warning('WARN');

        $additionals = $this->getFirstGraylogMessage()->getAllAdditionals();

        $this->assertArrayNotHasKey('trace_id', $additionals);
    }

    private function getFirstGraylogMessage(): Message
    {
        /** @var TestGraylogHandler $graylogHandler */
        $graylogHandler = $this->container->get('graylog_handler');
        /** @var FingersCrossedHandler $mainHandler */
        $mainHandler = $this->container->get('main_handler');

        $messages = $graylogHandler->flushPublishedMessages();
        $mainHandler->close();
        $messages = array_merge($messages, $graylogHandler->flushPublishedMessages());

        $this->assertNotEmpty($messages, 'Expected the record to reach the Graylog handler');

        return $messages[0];
    }

    private function getFirstSentryEvent(): Event
    {
        $this->container->get('sentry_client')->flush();

        /** @var TestTransport $transport */
        $transport = $this->container->get('sentry_transport');
        $events = $transport->getEvents();

        $this->assertNotEmpty($events, 'Expected the record to reach Sentry');

        return $events[0];
    }
}
