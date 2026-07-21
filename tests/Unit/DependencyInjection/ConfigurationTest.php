<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\DependencyInjection;

use Paysera\LoggingExtraBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider traceIdProviderProvider
     *
     * @param array<string, mixed> $input
     */
    public function testResolvesTraceIdProvider(array $input, ?string $expected): void
    {
        $config = $this->process(['application_name' => 'app-something'] + $input);

        $this->assertSame($expected, $config['trace_id_provider']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string|null}>
     */
    public static function traceIdProviderProvider(): array
    {
        return [
            'omitted defaults to null' => [[], null],
            'explicit null stays null' => [['trace_id_provider' => null], null],
            'configured id is kept' => [['trace_id_provider' => 'app.trace_id_provider'], 'app.trace_id_provider'],
            'surrounding whitespace is trimmed' => [['trace_id_provider' => '  app.trace_id_provider  '], 'app.trace_id_provider'],
        ];
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
