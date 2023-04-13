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
    /**
     * @var TestGraylogHandler
     */
    private $graylogHandler;

    /**
     * @var TestSentryTransport
     */
    private $sentryTransport;

    /**
     * @var ClientInterface
     */
    private $sentryClient;

    /**
     * @var FingersCrossedHandler
     */
    private $mainHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

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

    public function testSentryGetsContextValues()
    {
        $this->logger->error('Hello world', ['param1' => 'value1']);

        $event = $this->getSingleSentryEvent();
        $this->assertSame('value1', $event->getExtra()['param1'] ?? null);
    }

    public function testCorrelationId()
    {
        $this->logger->debug('debug');
        $this->logger->info('info');
        $this->logger->error('error');

        $correlationId = $this->getSingleSentryEvent()->getTags()['correlation_id'];
        $this->assertStringStartsWith('test-application-name', $correlationId);

        $correlationIds = array_map(function (Message $message) {
            return $message->getAdditional('correlation_id');
        }, $this->getGraylogMessages());

        $this->assertSame(array_fill(0, 3, $correlationId), $correlationIds);
    }

    public function testRootPrefix()
    {
        $this->logger->error(sprintf('Hey from %s. Over', __FILE__));

        $this->assertSame(
            'Hey from <root>/tests/Functional/FunctionalHandlersTest.php. Over',
            $this->getSingleSentryEvent()->getMessage()
        );
    }

    public function testIntrospection()
    {
        $this->logger->info('Info');
        $this->logger->error('Err');

        $messages = $this->getGraylogMessages();

        $this->assertArrayNotHasKey(
            'function',
            $messages[0]->getAllAdditionals(),
            'Introspection works only for errors'
        );

        $allAdditionals = $messages[1]->getAllAdditionals();

        $this->assertSame('testIntrospection', $allAdditionals['function'] ?? null);
        $this->assertSame(__CLASS__, $allAdditionals['class'] ?? null);
    }

    public function testExceptionGrouping()
    {
        $this->logger->error('Err 1', ['exception' => new ConnectionException('1')]);
        $this->logger->error('Err 2', ['exception' => new ConnectionException('2')]);

        $events = $this->getSentryEvents();

        $this->assertNotEmpty($events[0]->getFingerprint());
        $this->assertSame($events[0]->getFingerprint(), $events[1]->getFingerprint());
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
        $this->assertCount(1, $sentryEvents);
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
