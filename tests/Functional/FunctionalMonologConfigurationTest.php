<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Gelf\Message;
use Monolog\Handler\FingersCrossedHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestGraylogHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestSentryTransport;
use Psr\Log\LoggerInterface;
use Sentry\ClientInterface;
use Sentry\Event;

class FunctionalMonologConfigurationTest extends FunctionalTestCase
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

    public function testGraylog()
    {
        $this->logger->debug('DEBUG');
        $this->logger->info('INFO', ['param1' => 'value1']);
        $this->logger->warning('WARN');

        $this->assertSameGraylogMessages([
            ['message' => 'INFO', 'additionals' => ['ctxt_param1' => 'value1']],
            ['message' => 'WARN'],
        ], $this->getGraylogMessages());
    }

    public function testGraylogWithError()
    {
        $this->logger->info('INFO1');
        $this->logger->debug('DEBUG');
        $this->logger->info('INFO2');
        $this->logger->error('ERR');
        $this->logger->error('INFO3');

        $this->assertSameGraylogMessages([
            ['message' => 'INFO1'],
            ['message' => 'INFO2'],
            ['message' => 'DEBUG'],
            ['message' => 'ERR'],
            ['message' => 'INFO3'],
        ], $this->getGraylogMessages());
    }

    public function testGraylogWithLotsOfInfoMessages()
    {
        for ($i = 0; $i < 500; $i++) {
            $this->logger->info('INFO');
        }

        $this->assertSameGraylogMessages(
            array_fill(0, 500, ['message' => 'INFO']),
            $this->getGraylogMessages()
        );
    }

    public function testGraylogWithLotsOfDebugMessages()
    {
        for ($i = 0; $i < 500; $i++) {
            $this->logger->debug('msg');
        }
        $this->logger->error('msg');

        $this->assertSameGraylogMessages(
            array_fill(0, 50, ['message' => 'msg']),
            $this->getGraylogMessages()
        );
    }

    public function testSentry()
    {
        $this->logger->info('Hello world', ['param1' => 'value1']);
        $this->logger->warning('Hello world', ['param1' => 'value1']);
        $this->logger->error('Hello world', ['param1' => 'value1']);

        $this->assertEmpty($this->sentryTransport->getEvents());
        $this->sentryClient->flush();
        $this->assertSameSentryEvents([
            ['message' => 'Hello world', 'additionals' => [
                'monolog.channel' => 'app',
                'monolog.level' => 'ERROR',
            ]],
        ], $this->sentryTransport->getEvents());
    }

    private function getGraylogMessages()
    {
        $messages = $this->graylogHandler->flushPublishedMessages();
        $this->mainHandler->close();
        return array_merge($messages, $this->graylogHandler->flushPublishedMessages());
    }

    private function assertSameGraylogMessages(array $expectations, array $publishedMessages)
    {
        $this->assertCount(count($expectations), $publishedMessages);
        foreach ($expectations as $i => $expectation) {
            /** @var Message $message */
            $message = $publishedMessages[$i];
            $this->assertSame($expectation['message'], $message->getShortMessage());

            $additionalsExpectation = $expectation['additionals'] ?? [];
            foreach ($additionalsExpectation as $key => $value) {
                $this->assertArrayHasKey($key, $message->getAllAdditionals());
                $this->assertSame($value, $message->getAllAdditionals()[$key]);
            }

            $this->assertSame('test-application-name', $message->getHost());
            $this->assertSame('app', $message->getFacility());
        }
    }

    private function assertSameSentryEvents(array $expectations, array $publishedEvents)
    {
        $this->assertCount(count($expectations), $publishedEvents);
        foreach ($expectations as $i => $expectation) {
            /** @var Event $event */
            $event = $publishedEvents[$i];
            $this->assertSame($expectation['message'], $event->getMessage());

            $additionalsExpectation = $expectation['additionals'] ?? [];
            foreach ($additionalsExpectation as $key => $value) {
                $this->assertArrayHasKey($key, $event->getExtra());
                $this->assertSame($value, $event->getExtra()[$key]);
            }

            $this->assertSame('monolog.app', $event->getLogger());
            $this->assertSame('v123', $event->getRelease());
        }
    }
}
