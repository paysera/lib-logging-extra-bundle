<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\TraceIdProvider;
use PHPUnit\Framework\TestCase;

class TraceIdProviderTest extends TestCase
{
    public function testReturnsNullByDefault(): void
    {
        $provider = new TraceIdProvider();

        $this->assertNull($provider->getTraceId());
    }

    public function testSetAndGet(): void
    {
        $provider = new TraceIdProvider();

        $provider->setTraceId('abc-123');

        $this->assertSame('abc-123', $provider->getTraceId());
    }

    public function testResetTraceId(): void
    {
        $provider = new TraceIdProvider();

        $provider->setTraceId('abc-123');
        $provider->resetTraceId();

        $this->assertNull($provider->getTraceId());
    }

    public function testAcceptsMaxLengthValue(): void
    {
        $provider = new TraceIdProvider();
        $value = str_repeat('a', 200);

        $provider->setTraceId($value);

        $this->assertSame($value, $provider->getTraceId());
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testIgnoresInvalidValue(string $value): void
    {
        $provider = new TraceIdProvider();

        $provider->setTraceId($value);

        $this->assertNull($provider->getTraceId());
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testInvalidValueDoesNotClobberExistingValue(string $value): void
    {
        $provider = new TraceIdProvider();
        $provider->setTraceId('valid-id');

        $provider->setTraceId($value);

        $this->assertSame('valid-id', $provider->getTraceId());
    }

    public function provideInvalidValues(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 201)],
            'space' => ['trace id'],
            'newline injection' => ["trace-id\ninjected=value"],
            'tab' => ["trace-id\t123"],
            'structural punctuation' => ['trace-id{"key":"value"}'],
            'slash' => ['trace/id/123'],
        ];
    }
}
