<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

class GroupExceptionsProcessor implements ProcessorInterface
{
    private array $exceptionsClassesToGroup;

    public function __construct(array $exceptionsClassesToGroup)
    {
        $this->exceptionsClassesToGroup = array_flip($exceptionsClassesToGroup);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!isset($record['context']['exception'])) {
            return $record;
        }

        $exception = $record['context']['exception'];
        $exceptionClass = get_class($exception);

        if (isset($this->exceptionsClassesToGroup[$exceptionClass])) {
            SentrySdk::getCurrentHub()
                ->configureScope(function (Scope $scope) use ($exceptionClass) {
                    $scope->setFingerprint([$exceptionClass]);
                })
            ;
        }

        return $record;
    }
}
