<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Doctrine\DBAL\ConnectionException;
use Gelf\Message;
use Monolog\Handler\FingersCrossedHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestGraylogHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestSentryTransport;
use Psr\Log\LoggerInterface;
use Sentry\ClientInterface;
use Sentry\Event;

class FunctionalHandlersTest extends FunctionalTestCase
{
    private TestGraylogHandler $graylogHandler;
    private TestSentryTransport $sentryTransport;
    private ClientInterface $sentryClient;
    private FingersCrossedHandler $mainHandler;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->logger = $container->get('public_logger');
        $this->mainHandler = $container->get('main_handler');
        $this->graylogHandler = $container->get('graylog_handler');
        $this->sentryTransport = $container->get('sentry_transport');
        $this->sentryClient = $container->get('sentry_client');
    }

    public function testSentryGetsContextValues(): void
    {
        $this->logger->error('Hello world', ['param1' => 'value1']);

        $event = $this->getSingleSentryEvent();
        static::assertSame('value1', $event->getExtra()['param1'] ?? null);
    }

    public function testCorrelationId(): void
    {
        $this->logger->debug('debug');
        $this->logger->info('info');
        $this->logger->error('error');

        $correlationId = $this->getSingleSentryEvent()->getTags()['correlation_id'];
        static::assertStringStartsWith('test-application-name', $correlationId);

        $correlationIds = array_map(function (Message $message) {
            return $message->getAdditional('correlation_id');
        }, $this->getGraylogMessages());

        static::assertSame(array_fill(0, 4, $correlationId), $correlationIds);
    }

    public function testIntrospection(): void
    {
        $this->logger->info('Info');
        $this->logger->error('Err');

        $messages = $this->getGraylogMessages();

        static::assertArrayNotHasKey(
            'function',
            $messages[1]->getAllAdditionals(),
            'Introspection works only for errors'
        );

        $allAdditional = $messages[2]->getAllAdditionals();

        static::assertSame('testIntrospection', $allAdditional['function'] ?? null);
        static::assertSame(__CLASS__, $allAdditional['class'] ?? null);
    }

    public function testExceptionGrouping(): void
    {
        $this->logger->error('Err 1', ['exception' => new ConnectionException('1')]);
        $this->logger->error('Err 2', ['exception' => new ConnectionException('2')]);

        $events = $this->getSentryEvents();

        static::assertNotEmpty($events[0]->getFingerprint());
        static::assertSame($events[0]->getFingerprint(), $events[1]->getFingerprint());
    }

    /**
     * @return array|Event[]
     */
    private function getSentryEvents(): array
    {
        $this->sentryClient->flush();

        return $this->sentryTransport->getEvents();
    }

    private function getSingleSentryEvent(): Event
    {
        $sentryEvents = $this->getSentryEvents();
        static::assertCount(1, $sentryEvents);

        return $sentryEvents[0];
    }

    /**
     * @return array|Message[]
     */
    private function getGraylogMessages(): array
    {
        $this->mainHandler->close();

        return $this->graylogHandler->flushPublishedMessages();
    }
}
