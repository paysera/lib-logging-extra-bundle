<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use DateTimeInterface;
use Doctrine\Persistence\Proxy;
use Doctrine\ORM\PersistentCollection;
use Monolog\Utils;
use Throwable;

trait FormatterTrait
{
    protected function normalize(mixed $data, $depth = 0): mixed
    {
        $prenormalizedData = $this->preNormalizeData($data, $depth);

        return parent::normalize($prenormalizedData, $depth);
    }

    private function preNormalizeData($data, $depth)
    {
        if ($depth > 2) {
            return $this->getScalarRepresentation($data);
        }

        if ($data instanceof PersistentCollection) {
            return $data->isInitialized() ? iterator_to_array($data) : get_class($data);
        }

        if ($data instanceof Proxy) {
            return $this->normalizeProxy($data);
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

    private function getScalarRepresentation(mixed $data): mixed
    {
        if (is_scalar($data) || $data === null) {
            return $data;
        }

        if (is_object($data)) {
            return get_class($data);
        }

        return gettype($data);
    }

    private function normalizeObject(mixed $data): array
    {
        $result = [];
        foreach ((array)$data as $key => $value) {
            $parts = explode("\0", $key);
            $fixedKey = end($parts);
            if (str_starts_with($fixedKey, '__')) {
                continue;
            }

            $result[$fixedKey] = $value;
        }

        return $result;
    }

    private function normalizeProxy(Proxy $data): array|string
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
        return Utils::jsonEncode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            $ignoreErrors
        );
    }
}
