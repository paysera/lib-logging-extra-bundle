<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use Monolog\Formatter\GelfMessageFormatter as MonologGelfMessageFormatter;

class GelfMessageFormatter extends MonologGelfMessageFormatter
{
    use FormatterTrait;
}
