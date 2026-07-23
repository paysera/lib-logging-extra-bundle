<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\DependencyInjection;

use Paysera\LoggingExtraBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testKeepsApplicationName(): void
    {
        $config = $this->process(['application_name' => 'app-something']);

        $this->assertSame('app-something', $config['application_name']);
    }

    public function testRequiresApplicationName(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([]);
    }

    public function testKeepsGroupedExceptions(): void
    {
        $config = $this->process([
            'application_name' => 'app-something',
            'grouped_exceptions' => ['Doctrine\DBAL\ConnectionException'],
        ]);

        $this->assertSame(['Doctrine\DBAL\ConnectionException'], $config['grouped_exceptions']);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
