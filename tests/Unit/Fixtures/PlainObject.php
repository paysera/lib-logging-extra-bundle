<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures;

class PlainObject
{
    public int $count;
    private string $name;

    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }
}
