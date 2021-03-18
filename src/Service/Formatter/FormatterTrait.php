<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use DateTimeInterface;
use Doctrine\Common\Persistence\Proxy as LegacyProxy;
use Doctrine\Persistence\Proxy;
use Doctrine\ORM\PersistentCollection;
use Monolog\Utils;
use Throwable;

/**
 * To be used on classes extending NormalizerFormatter
 */
trait FormatterTrait
{
    protected function normalize($data, $depth = 0)
    {
        $prenormalizedData = $this->prenormalizeData($data, $depth);

        return parent::normalize($prenormalizedData, $depth);
    }

    private function prenormalizeData($data, $depth)
    {
        if ($depth > 2) {
            return $this->getScalarRepresentation($data);
        }

        if ($data instanceof PersistentCollection) {
            return $data->isInitialized() ? iterator_to_array($data) : get_class($data);
        }

        if ($data instanceof Proxy || $data instanceof LegacyProxy) {
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

    /**
     * @param $data
     * @param bool $ignoreErrors
     *
     * @return string
     */
    protected function toJson($data, $ignoreErrors = false)
    {
        return Utils::jsonEncode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            $ignoreErrors
        );
    }
}
