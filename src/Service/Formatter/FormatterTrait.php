<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Proxy;
use DateTimeInterface;
use Monolog\Logger;
use Monolog\Utils;
use Throwable;

trait FormatterTrait
{
    /** @var string[] */
    private static $sensitiveKeys = [
        'password',
        'secret',
        'apikey',
        'apisecret',
        'apisecretkey',
        'secretkey',
        'credentials',
    ];
    /**
     * Normalizes given data with pre-processing for Doctrine entities and collections.
     *
     * @param mixed $data
     * @param int $depth
     * @return mixed
     */
    protected function normalizeWithPrenormalization($data, $depth = 0)
    {
        $prenormalizedData = $this->prenormalizeData($data, $depth);

        return parent::normalize($prenormalizedData, $depth);
    }

    private function prenormalizeData($data, $depth)
    {
        // Monolog 1.x and 2.x have different depth tracking:
        // - Monolog 1.x: entity properties are at depth 3, nested at depth 4
        // - Monolog 2.x: entity properties are at depth 2, nested at depth 3
        // We detect Monolog version using the Logger::API constant (1 for 1.x, 2 for 2.x, 3 for 3.x)
        $maxDepthForExpansion = (defined('Monolog\Logger::API') && Logger::API >= 2) ? 2 : 3;

        if ($data instanceof PersistentCollection) {
            $isInitialized = $data->isInitialized();

            // Only expand initialized collections up to the threshold depth
            // This allows expansion for direct properties of logged entities, but not for nested entities
            // When expanded, return array of class names for entities
            if ($isInitialized && $depth <= $maxDepthForExpansion) {
                $result = [];
                foreach ($data as $entity) {
                    // Convert entities in collections to just their class name
                    $result[] = is_object($entity) ? get_class($entity) : $entity;
                }
                return $result;
            }
            // Always return class name for uninitialized or deep collections
            return get_class($data);
        }

        // Always normalize Proxies regardless of depth to get at least the ID
        if ($data instanceof Proxy || is_a($data, 'Doctrine\Common\Persistence\Proxy')) {
            return $this->normalizeProxy($data);
        }

        if ($depth > $maxDepthForExpansion) {
            return $this->getScalarRepresentation($data);
        }

        if (
            is_object($data)
            && !$data instanceof DateTimeInterface
            && !$data instanceof Throwable
        ) {
            return $this->normalizeObject($data);
        }

        return $data;
    }

    private function getScalarRepresentation($data)
    {
        if (is_scalar($data) || $data === null) {
            return $data;
        }

        if (is_object($data)) {
            return get_class($data);
        }

        return gettype($data);
    }

    private function normalizeObject($data)
    {
        $result = [];
        foreach ((array)$data as $key => $value) {
            $parts = explode("\0", $key);
            $fixedKey = end($parts);
            if (substr($fixedKey, 0, 2) === '__') {
                continue;
            }

            $normalizedKey = preg_replace('/[^a-z]/', '', strtolower($fixedKey));
            if (in_array($normalizedKey, self::$sensitiveKeys, true)) {
                $result[$fixedKey] = '***';
                continue;
            }

            $result[$fixedKey] = $value;
        }

        return $result;
    }

    private function normalizeProxy(Proxy $data)
    {
        if ($data->__isInitialized()) {
            return $this->normalizeObject($data);
        }

        if (method_exists($data, 'getId')) {
            return ['id' => $data->getId()];
        }

        return '[Uninitialized]';
    }

    protected function toJson($data, $ignoreErrors = false): string
    {
        // Monolog 2.x has Utils::jsonEncode(), Monolog 1.x does not
        if (method_exists(Utils::class, 'jsonEncode')) {
            return Utils::jsonEncode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                $ignoreErrors
            );
        }

        // Fallback for Monolog 1.x
        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($ignoreErrors ? 0 : JSON_THROW_ON_ERROR)
        );
    }
}
