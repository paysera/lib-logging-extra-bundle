<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\ParentCorrelationIdProvider;
use PHPUnit\Framework\TestCase;

class ParentCorrelationIdProviderTest extends TestCase
{
    public function testReturnsNullByDefault(): void
    {
        $provider = new ParentCorrelationIdProvider();

        $this->assertNull($provider->getParentCorrelationId());
    }

    /**
     * @dataProvider provideValidValues
     */
    public function testSetAndGet(string $value): void
    {
        $provider = new ParentCorrelationIdProvider();

        $provider->setParentCorrelationId($value);

        $this->assertSame($value, $provider->getParentCorrelationId());
    }

    public function provideValidValues(): array
    {
        return [
            'plain id' => ['abc-123'],
            'max length' => [str_repeat('a', 128)],
        ];
    }

    public function testResetParentCorrelationId(): void
    {
        $provider = new ParentCorrelationIdProvider();

        $provider->setParentCorrelationId('abc-123');
        $provider->resetParentCorrelationId();

        $this->assertNull($provider->getParentCorrelationId());
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testIgnoresInvalidValue(string $value): void
    {
        $provider = new ParentCorrelationIdProvider();

        $provider->setParentCorrelationId($value);

        $this->assertNull($provider->getParentCorrelationId());
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testInvalidValueDoesNotClobberExistingValue(string $value): void
    {
        $provider = new ParentCorrelationIdProvider();
        $provider->setParentCorrelationId('valid-id');

        $provider->setParentCorrelationId($value);

        $this->assertSame('valid-id', $provider->getParentCorrelationId());
    }

    public function provideInvalidValues(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 129)],
            'space' => ['parent id'],
            'newline injection' => ["parent-id\ninjected=value"],
            'tab' => ["parent-id\t123"],
            'structural punctuation' => ['parent-id{"key":"value"}'],
            'slash' => ['parent/id/123'],
        ];
    }
}
