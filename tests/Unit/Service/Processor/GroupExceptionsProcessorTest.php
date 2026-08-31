<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Processor;

use Paysera\LoggingExtraBundle\Service\Processor\GroupExceptionsProcessor;
use Paysera\LoggingExtraBundle\Tests\Unit\Support\MonologRecordTrait;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Hub;

class GroupExceptionsProcessorTest extends TestCase
{
    use MonologRecordTrait;

    private GroupExceptionsProcessor $processor;

    protected function setUp(): void
    {
        SentrySdk::setCurrentHub(new Hub());
        $this->processor = new GroupExceptionsProcessor([\RuntimeException::class]);
    }

    public function testIgnoresStringExceptionContext(): void
    {
        $record = $this->buildLogRecord(['context' => ['exception' => 'something went wrong']]);

        $result = ($this->processor)($record);

        $this->assertSame(
            ['exception' => 'something went wrong'],
            $this->getRecordField($result, 'context'),
        );
        $this->assertSame([], $this->getCurrentFingerprint());
    }

    public function testSetsFingerprintForGroupedException(): void
    {
        $record = $this->buildLogRecord(['context' => ['exception' => new \RuntimeException('boom')]]);

        ($this->processor)($record);

        $this->assertSame([\RuntimeException::class], $this->getCurrentFingerprint());
    }

    public function testDoesNotSetFingerprintForUngroupedException(): void
    {
        $record = $this->buildLogRecord(['context' => ['exception' => new \LogicException('boom')]]);

        ($this->processor)($record);

        $this->assertSame([], $this->getCurrentFingerprint());
    }

    public function testIgnoresRecordWithoutExceptionContext(): void
    {
        $record = $this->buildLogRecord();

        $result = ($this->processor)($record);

        $this->assertSame([], $this->getRecordField($result, 'context'));
        $this->assertSame([], $this->getCurrentFingerprint());
    }

    private function getCurrentFingerprint(): array
    {
        $fingerprint = [];
        SentrySdk::getCurrentHub()->configureScope(function ($scope) use (&$fingerprint) {
            $event = $scope->applyToEvent(Event::createEvent());
            $fingerprint = $event !== null ? $event->getFingerprint() : [];
        });

        return $fingerprint;
    }
}
