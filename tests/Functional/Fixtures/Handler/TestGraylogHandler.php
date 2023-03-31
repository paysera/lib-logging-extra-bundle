<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler;

use Monolog\Handler\GelfHandler;
use Monolog\LogRecord;

class TestGraylogHandler extends GelfHandler
{
    private array $publishedMessages = [];

    protected function write(LogRecord $record): void
    {
        $this->publishedMessages[] = $record['formatted'];
    }

    public function flushPublishedMessages(): array
    {
        $messages = $this->publishedMessages;
        $this->publishedMessages = [];

        return $messages;
    }
}
