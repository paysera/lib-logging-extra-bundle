<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional;

use Closure;
use Gelf\Message;
use Monolog\Handler\FingersCrossedHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Entity\PersistedEntity;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Handler\TestGraylogHandler;
use Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Logger\TestDbalLogger;
use Psr\Log\LoggerInterface;

class FunctionalFormattersTest extends FunctionalTestCase
{
    private TestGraylogHandler $graylogHandler;
    private FingersCrossedHandler $mainHandler;
    private LoggerInterface $logger;
    private TestDbalLogger $dbalLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->logger = $container->get('public_logger');
        $this->mainHandler = $container->get('main_handler');
        $this->graylogHandler = $container->get('graylog_handler');
        $this->dbalLogger = $container->get('dbal_logger');
    }

    public function testWithDoctrineEntity(): void
    {
        $this->setUpDatabase();

        $manager = $this->getEntityManager();
        $child = (new PersistedEntity())
            ->setField('1.2')
            ->addChild(
                (new PersistedEntity())
                    ->setField('1.2.1')
            )
        ;
        $entity = (new PersistedEntity())
            ->setField('1')
            ->addChild(
                (new PersistedEntity())
                    ->setField('1.1')
                    ->addChild(
                        (new PersistedEntity())
                            ->setField('1.1.1')
                    )
                    ->addChild(
                        (new PersistedEntity())
                            ->setField('1.1.2')
                            ->addChild(
                                (new PersistedEntity())
                                    ->setField('1.1.2.1')
                            )
                    )
            )
            ->addChild($child)
        ;
        $manager->persist($entity);
        $manager->flush();
        $manager->clear();
        $this->graylogHandler->flushPublishedMessages();

        /** @var PersistedEntity $child */
        $child = $manager->find(PersistedEntity::class, $child->getId());
        $this->assertWithoutQueries(function () use ($child) {
            // children and parent are not loaded yet
            $this->logger->info('INFO', ['entity' => $child]);
        });

        /** @var PersistedEntity $entity */
        $entity = $manager->find(PersistedEntity::class, $entity->getId());

        $this->assertWithoutQueries(function () use ($entity) {
            // this is only a proxy with just ID
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $entity->setField('Modified');
        $this->assertWithoutQueries(function () use ($entity) {
            // we've initialized the proxy and changed the field value
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $entity->getChildren()->count();
        $this->assertWithoutQueries(function () use ($entity) {
            // we've initialized children collection
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $this->assertWithoutQueries(function () use ($child) {
            // parent and children were initialized
            $this->logger->info('INFO', ['entity' => $child]);
        });

        /** @var Message[] $messages */
        $messages = $this->assertWithoutQueries(function () {
            return $this->getGraylogMessages();
        });
        static::assertCount(5, $messages, 'Probably an error in normalization');

        static::assertSame(
            json_encode([
                'id' => 6,
                'field' => '1.2',
                'parent' => ['id' => 1],
                'children' => 'Doctrine\\ORM\\PersistentCollection',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[0]->getAdditional('ctxt_entity')
        );
        static::assertSame(
            json_encode([
                'id' => 1,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[1]->getAdditional('ctxt_entity')
        );
        static::assertSame(
            json_encode([
                'id' => 1,
                'field' => 'Modified',
                'parent' => null,
                'children' => 'Doctrine\\ORM\\PersistentCollection',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[2]->getAdditional('ctxt_entity')
        );
        static::assertSame(
            json_encode([
                'id' => 1,
                'field' => 'Modified',
                'parent' => null,
                'children' => [
                    'Paysera\\LoggingExtraBundle\\Tests\\Functional\\Fixtures\\Entity\\PersistedEntity',
                    'Paysera\\LoggingExtraBundle\\Tests\\Functional\\Fixtures\\Entity\\PersistedEntity',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[3]->getAdditional('ctxt_entity')
        );
        static::assertSame(
            json_encode([
                'id' => 6,
                'field' => '1.2',
                'parent' => [
                    'id' => 1,
                    'field' => 'Modified',
                    'parent' => null,
                    'children' => 'Doctrine\\ORM\\PersistentCollection',
                ],
                'children' => 'Doctrine\\ORM\\PersistentCollection',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[4]->getAdditional('ctxt_entity')
        );
    }

    /**
     * @return array|Message[]
     */
    private function getGraylogMessages(): array
    {
        $this->mainHandler->close();

        return $this->graylogHandler->flushPublishedMessages();
    }

    private function assertWithoutQueries(Closure $closure): mixed
    {
        $count = $this->dbalLogger->getQueryCount();
        $result = $closure();
        static::assertSame($count, $this->dbalLogger->getQueryCount(), 'No queries must be made by logger');

        return $result;
    }
}
