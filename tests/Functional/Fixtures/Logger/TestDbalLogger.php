<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Logger;

use Symfony\Bridge\Doctrine\Logger\DbalLogger;

class TestDbalLogger extends DbalLogger
{
    private int $queryCount = 0;

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        parent::startQuery($sql, $params, $types);
        $this->queryCount++;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
}
