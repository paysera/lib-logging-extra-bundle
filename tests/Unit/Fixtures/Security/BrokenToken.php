<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security;

use RuntimeException;

class BrokenToken extends Token
{
    public function getUserIdentifier(): string
    {
        throw new RuntimeException('User identifier cannot be read');
    }
}
