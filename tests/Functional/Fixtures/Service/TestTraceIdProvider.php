<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Service;

use Paysera\LoggingExtraBundle\Service\TraceIdProviderInterface;

class TestTraceIdProvider implements TraceIdProviderInterface
{
    public const TRACE_ID = 'test-trace-id';

    public function getTraceId(): ?string
    {
        return self::TRACE_ID;
    }
}
