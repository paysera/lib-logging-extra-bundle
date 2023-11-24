<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\TestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class FunctionalTestCase extends TestCase
{
    /**
     * @var TestKernel
     */
    protected $kernel;

    /**
     * @param string $testCase
     * @param string $commonFile
     * @return ContainerInterface
     */
    protected function setUpContainer($testCase, $commonFile = 'common.yml')
    {
        $this->kernel = new TestKernel($testCase, $commonFile);
        $this->kernel->boot();
        return $this->kernel->getContainer();
    }

    protected function tearDown(): void
    {
        $container = $this->kernel->getContainer();
        $this->kernel->shutdown();
        if ($container instanceof ResettableContainerInterface || $container instanceof ResetInterface) {
            $container->reset();
        }
        $filesystem = new Filesystem();
        $filesystem->remove($this->kernel->getCacheDir());
    }

    protected function makeGetRequest(string $uri): Response
    {
        return $this->handleRequest($this->createRequest('GET', $uri));
    }

    protected function createRequest(
        string $method,
        string $uri,
        string $content = null,
        array $headers = [],
        string $username = null
    ): Request {
        $parts = parse_url($uri);
        parse_str($parts['query'] ?? '', $query);
        $request = new Request($query, [], [], [], [], array_filter([
            'REQUEST_URI' => $parts['path'],
            'REQUEST_METHOD' => $method,
            'HTTP_PHP_AUTH_USER' => $username,
            'HTTP_PHP_AUTH_PW' => $username !== null ? 'pass' : null,
        ]), $content);
        $request->headers->add($headers);
        return $request;
    }

    protected function handleRequest(Request $request): Response
    {
        try {
            return $this->kernel->handle($request);
        } catch (HttpException $exception) {
            return new Response('', $exception->getStatusCode(), $exception->getHeaders());
        }
    }

    protected function setUpDatabase()
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metadata, true);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
    }
}
