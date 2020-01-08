<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler;

use Monolog\Handler\GelfHandler;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_code_style_default_values_in_constructor
 */
class TestGraylogHandler extends GelfHandler
{
    private $publishedMessages = [];

    protected function write(array $record): void
    {
        $this->publishedMessages[] = $record['formatted'];
    }

    public function flushPublishedMessages()
    {
        $messages = $this->publishedMessages;
        $this->publishedMessages = [];
        return $messages;
    }
}
