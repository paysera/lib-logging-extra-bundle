<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Sentry\Event;
use Sentry\Transport\ClosableTransportInterface;
use Sentry\Transport\TransportInterface;

class TestSentryTransport implements TransportInterface, ClosableTransportInterface
{
    private $pendingEvents;
    private $events;
    private $id;

    public function __construct()
    {
        $this->pendingEvents = [];
        $this->events = [];
        $this->id = 0;
    }

    public function send(Event $event): ?string
    {
        $this->pendingEvents[] = $event;
        return (string)$this->id++;
    }

    public function close(?int $timeout = null): PromiseInterface
    {
        $this->events = $this->pendingEvents;
        $this->pendingEvents = [];

        return new FulfilledPromise(true);
    }

    public function getEvents()
    {
        return $this->events;
    }
}
