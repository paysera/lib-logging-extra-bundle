<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

class ParentCorrelationIdProvider
{
    private const MAX_LENGTH = 128;

    private const PATTERN = '/^[A-Za-z0-9._-]+\z/';

    private ?string $parentCorrelationId;

    public function __construct()
    {
        $this->parentCorrelationId = null;
    }

    public function getParentCorrelationId(): ?string
    {
        return $this->parentCorrelationId;
    }

    public function setParentCorrelationId(string $parentCorrelationId): void
    {
        if (!self::isValid($parentCorrelationId)) {
            return;
        }

        $this->parentCorrelationId = $parentCorrelationId;
    }

    public function resetParentCorrelationId(): void
    {
        $this->parentCorrelationId = null;
    }

    private static function isValid(string $parentCorrelationId): bool
    {
        return $parentCorrelationId !== ''
            && strlen($parentCorrelationId) <= self::MAX_LENGTH
            && preg_match(self::PATTERN, $parentCorrelationId) === 1;
    }
}
