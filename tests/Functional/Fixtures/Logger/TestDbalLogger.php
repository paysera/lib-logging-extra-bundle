<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Logger;

use Symfony\Bridge\Doctrine\Logger\DbalLogger;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_code_style_default_values_in_constructor
 */
class TestDbalLogger extends DbalLogger
{
    private $queryCount = 0;

    public function startQuery($sql, array $params = null, array $types = null)
    {
        parent::startQuery($sql, $params, $types);
        $this->queryCount++;
    }

    public function getQueryCount()
    {
        return $this->queryCount;
    }
}
