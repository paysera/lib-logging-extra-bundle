<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Handler;

use Monolog\Handler\HandlerWrapper;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\LogRecord;
use Sentry\State\Scope;

use function Sentry\withScope;

final class SentryExtraInformationHandler extends HandlerWrapper
{
    use ProcessableHandlerTrait;

    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $result = false;
        $record = $this->processRecord($record);

        $record['formatted'] = $this->getFormatter()->format($record);

        withScope(function (Scope $scope) use ($record, &$result): void {
            if (isset($record['context']) && \is_array($record['context'])) {
                foreach ($record['context'] as $key => $value) {
                    $scope->setExtra((string) $key, $value);
                }
            }

            if (isset($record['extra']) && \is_array($record['extra'])) {
                foreach ($record['extra'] as $key => $value) {
                    $scope->setTag($key, (string)$value);
                }
            }

            $result = $this->handler->handle($record);
        });

        return $result;
    }
}
