<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\DependencyInjection;

use Paysera\LoggingExtraBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testTraceIdProviderDefaultsToNull(): void
    {
        $config = $this->process(['application_name' => 'app-something']);

        $this->assertNull($config['trace_id_provider']);
    }

    public function testKeepsConfiguredTraceIdProvider(): void
    {
        $config = $this->process([
            'application_name' => 'app-something',
            'trace_id_provider' => 'app.trace_id_provider',
        ]);

        $this->assertSame('app.trace_id_provider', $config['trace_id_provider']);
    }

    public function testAllowsExplicitNullTraceIdProvider(): void
    {
        $config = $this->process([
            'application_name' => 'app-something',
            'trace_id_provider' => null,
        ]);

        $this->assertNull($config['trace_id_provider']);
    }

    /**
     * @dataProvider blankTraceIdProviderProvider
     */
    public function testRejectsBlankTraceIdProvider(string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a non-empty service id');

        $this->process([
            'application_name' => 'app-something',
            'trace_id_provider' => $value,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blankTraceIdProviderProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
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
