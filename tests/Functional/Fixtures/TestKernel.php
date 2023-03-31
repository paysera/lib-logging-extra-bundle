<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Paysera\LoggingExtraBundle\PayseraLoggingExtraBundle;
use Sentry\SentryBundle\SentryBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class TestKernel extends Kernel
{
    public function __construct(private string $testCaseFile, private string $commonFile = 'common.yml')
    {
        parent::__construct((string)crc32($testCaseFile . $commonFile), true);
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new MonologBundle(),
            new SentryBundle(),
            new PayseraLoggingExtraBundle(),
            new DoctrineBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/' . $this->commonFile);
        $loader->load(__DIR__ . '/config/cases/' . $this->testCaseFile);
    }
}
