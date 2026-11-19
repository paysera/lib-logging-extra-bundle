<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures;

class PlainObject
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }
}
