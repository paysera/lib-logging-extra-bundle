<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

class ParentCorrelationIdProvider
{
    private ?string $parentCorrelationId = null;

    public function getParentCorrelationId(): ?string
    {
        return $this->parentCorrelationId;
    }

    public function setParentCorrelationId(?string $parentCorrelationId): void
    {
        $this->parentCorrelationId = $parentCorrelationId;
    }
}
