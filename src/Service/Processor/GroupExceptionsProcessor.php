<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_code_style_chained_method_calls
 */
if (class_exists('Monolog\LogRecord')) {
    // Monolog v3+ - has LogRecord class with typed ProcessorInterface
    class GroupExceptionsProcessor implements ProcessorInterface
    {
        private $exceptionsClassesToGroup;

        public function __construct(array $exceptionsClassesToGroup)
        {
            $this->exceptionsClassesToGroup = array_flip($exceptionsClassesToGroup);
        }

        public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
        {
            if (!isset($record->context['exception'])) {
                return $record;
            }

            $exception = $record->context['exception'];
            if (!is_object($exception)) {
                return $record;
            }

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
} else {
    // Monolog v1/v2 - uses array with untyped ProcessorInterface
    class GroupExceptionsProcessor implements ProcessorInterface
    {
        private $exceptionsClassesToGroup;

        public function __construct(array $exceptionsClassesToGroup)
        {
            $this->exceptionsClassesToGroup = array_flip($exceptionsClassesToGroup);
        }

        /**
         * @param array $record
         * @return array
         */
        public function __invoke($record)
        {
            if (!isset($record['context']['exception'])) {
                return $record;
            }

            $exception = $record['context']['exception'];
            if (!is_object($exception)) {
                return $record;
            }

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
}
