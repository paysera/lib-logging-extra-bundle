<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

interface TraceIdProviderInterface
{
    public function getTraceId(): ?string;
}
