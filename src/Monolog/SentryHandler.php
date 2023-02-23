<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Monolog;

use Monolog\Handler\HandlerWrapper;
use Monolog\Handler\ProcessableHandlerTrait;
use Sentry\State\Scope;
use function Sentry\withScope;

final class SentryHandler extends HandlerWrapper
{
    use ProcessableHandlerTrait;

    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $result = false;
        $record = $this->processRecord($record);

        $record['formatted'] = $this->getFormatter()->format($record);

        withScope(function (Scope $scope) use ($record, &$result): void {
            if (isset($record['context']['extra']) && \is_array($record['context']['extra'])) {
                foreach ($record['context']['extra'] as $key => $value) {
                    $scope->setExtra((string) $key, $value);
                }
            }

            if (isset($record['context']['tags']) && \is_array($record['context']['tags'])) {
                foreach ($record['context']['tags'] as $key => $value) {
                    $scope->setTag($key, $value);
                }
            }

            $result = $this->handler->handle($record);
        });

        return $result;
    }
}
