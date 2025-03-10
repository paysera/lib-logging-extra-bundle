<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;

class CorrelationIdFromHeaderExtractor
{
    public function getCorrelationId(): ?string
    {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', CorrelationIdListener::HEADER_NAME));

        return $_SERVER[$headerName] ?? null;
    }
}
