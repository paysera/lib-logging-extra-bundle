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

/**
 * @php-cs-fixer-ignore Paysera/php_basic_code_style_splitting_in_several_lines
 */
class FunctionalFormattersTest extends FunctionalTestCase
{
    /**
     * @var TestGraylogHandler
     */
    private $graylogHandler;

    /**
     * @var FingersCrossedHandler
     */
    private $mainHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TestDbalLogger
     */
    private $dbalLogger;

    protected function setUp()
    {
        parent::setUp();

        $container = $this->setUpContainer('basic.yml');
        $this->logger = $container->get('public_logger');
        $this->mainHandler = $container->get('main_handler');
        $this->graylogHandler = $container->get('graylog_handler');
        $this->dbalLogger = $container->get('dbal_logger');
    }

    public function testWithDoctrineEntity()
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

        /** @var PersistedEntity $child */
        $child = $manager->find(PersistedEntity::class, $child->getId());
        $this->assertWithoutQueries(function () use ($child) {
            $this->logger->info('INFO', ['entity' => $child]);
        });

        /** @var PersistedEntity $entity */
        $entity = $manager->find(PersistedEntity::class, $entity->getId());

        $this->assertWithoutQueries(function () use ($entity) {
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $entity->setField('Modified');
        $this->assertWithoutQueries(function () use ($entity) {
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $entity->getChildren()->count();
        $this->assertWithoutQueries(function () use ($entity) {
            $this->logger->info('INFO', ['entity' => $entity]);
        });

        $this->assertWithoutQueries(function () use ($child) {
            $this->logger->info('INFO', ['entity' => $child]);
        });

        /** @var Message[] $messages */
        $messages = $this->assertWithoutQueries(function () {
            return $this->getGraylogMessages();
        });
        $this->assertCount(5, $messages, 'Probably an error in normalization');

        $this->assertSame(
            json_encode([
                'id' => 6,
                'field' => '1.2',
                'parent' => ['id' => 1],
                'children' => '[Uninitialized]',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[0]->getAdditional('ctxt_entity')
        );
        $this->assertSame(
            json_encode([
                'id' => 1,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[1]->getAdditional('ctxt_entity')
        );
        $this->assertSame(
            json_encode([
                'id' => 1,
                'field' => 'Modified',
                'parent' => null,
                'children' => '[Uninitialized]',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $messages[2]->getAdditional('ctxt_entity')
        );
        $this->assertSame(
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
        $this->assertSame(
            json_encode([
                'id' => 6,
                'field' => '1.2',
                'parent' => [
                    'id' => 1,
                    'field' => 'Modified',
                    'parent' => null,
                    'children' => 'Doctrine\\ORM\\PersistentCollection',
                ],
                'children' => '[Uninitialized]',
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

    private function assertWithoutQueries(Closure $closure)
    {
        $count = $this->dbalLogger->getQueryCount();
        $result = $closure();
        $this->assertSame($count, $this->dbalLogger->getQueryCount(), 'No queries must be made by logger');
        return $result;
    }
}
