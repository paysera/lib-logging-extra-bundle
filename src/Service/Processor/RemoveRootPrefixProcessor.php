<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Processor;

use Monolog\Processor\ProcessorInterface;
use InvalidArgumentException;

class RemoveRootPrefixProcessor implements ProcessorInterface
{
    private string $rootPrefix;

    public function __construct(string $rootPrefix)
    {
        $realPath = realpath($rootPrefix);
        if ($realPath === false) {
            throw new InvalidArgumentException('Invalid root prefix specified');
        }
        $this->rootPrefix = $realPath;
    }

    public function __invoke(array $record)
    {
        $record['message'] = str_replace($this->rootPrefix, '<root>', $record['message']);

        return $record;
    }
}
