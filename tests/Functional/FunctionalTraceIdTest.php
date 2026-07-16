<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Gelf\Message;
use Monolog\Handler\FingersCrossedHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestGraylogHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Service\TestTraceIdProvider;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\TestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class FunctionalTraceIdTest extends TestCase
{
    /**
     * @var TestKernel|null
     */
    private $kernel;

    protected function tearDown(): void
    {
        if ($this->kernel === null) {
            return;
        }

        (new Filesystem())->remove($this->kernel->getCacheDir());
        $this->kernel = null;
    }

    public function testAddsTraceIdWhenProviderIsConfigured(): void
    {
        $container = $this->bootKernel('trace_id.yml');
        $container->get('public_logger')->warning('WARN');

        $additionals = $this->getFirstGraylogMessage($container)->getAllAdditionals();

        $this->assertArrayHasKey('trace_id', $additionals);
        $this->assertSame(TestTraceIdProvider::TRACE_ID, $additionals['trace_id']);
    }

    public function testOmitsTraceIdWhenNoProviderIsConfigured(): void
    {
        $container = $this->bootKernel('basic.yml');
        $container->get('public_logger')->warning('WARN');

        $additionals = $this->getFirstGraylogMessage($container)->getAllAdditionals();

        $this->assertArrayNotHasKey('trace_id', $additionals);
    }

    public function testFailsToCompileWhenProviderIsMisspelled(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('test_trace_id_provider_typo');

        $this->bootKernel('trace_id_misspelled_provider.yml');
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function bootKernel(string $testCase)
    {
        $this->kernel = new TestKernel($testCase);
        (new Filesystem())->remove($this->kernel->getCacheDir());
        $this->kernel->boot();

        return $this->kernel->getContainer();
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    private function getFirstGraylogMessage($container): Message
    {
        /** @var TestGraylogHandler $graylogHandler */
        $graylogHandler = $container->get('graylog_handler');
        /** @var FingersCrossedHandler $mainHandler */
        $mainHandler = $container->get('main_handler');

        $messages = $graylogHandler->flushPublishedMessages();
        $mainHandler->close();
        $messages = array_merge($messages, $graylogHandler->flushPublishedMessages());

        $this->assertNotEmpty($messages, 'Expected the record to reach the Graylog handler');

        return $messages[0];
    }
}
