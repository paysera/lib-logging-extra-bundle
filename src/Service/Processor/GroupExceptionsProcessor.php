<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use Sentry\SentryBundle\SentryBundle;
use Sentry\State\Scope;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_code_style_chained_method_calls
 */
class GroupExceptionsProcessor implements ProcessorInterface
{
    private $exceptionsClassesToGroup;

    public function __construct(array $exceptionsClassesToGroup)
    {
        $this->exceptionsClassesToGroup = array_flip($exceptionsClassesToGroup);
    }

    public function __invoke(array $record)
    {
        if (!isset($record['context']['exception'])) {
            return $record;
        }

        $exception = $record['context']['exception'];
        $exceptionClass = get_class($exception);

        if (isset($this->exceptionsClassesToGroup[$exceptionClass])) {
            SentryBundle::getCurrentHub()
                ->configureScope(function (Scope $scope) use ($exceptionClass) {
                    $scope->setFingerprint([$exceptionClass]);
                })
            ;
        }

        return $record;
    }
}
