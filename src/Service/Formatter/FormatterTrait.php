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
    /**
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
        $maxDepthForExpansion = (defined('Monolog\Logger::API') && Logger::API >= 2) ? 2 : 3;

        if ($data instanceof PersistentCollection) {
            if ($data->isInitialized() && $depth <= $maxDepthForExpansion) {
                $result = [];
                foreach ($data as $entity) {
                    $result[] = is_object($entity) ? get_class($entity) : $entity;
                }
                return $result;
            }
            return get_class($data);
        }

        if (
            is_a($data, 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            || (
                is_a($data, 'Symfony\Component\Security\Core\User\UserInterface')
                && !$this->isUninitializedProxy($data)
            )
        ) {
            return $this->normalizeSecurityObject($data);
        }

        if ($this->isProxy($data)) {
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

            $result[$fixedKey] = $value;
        }

        return $result;
    }

    private function normalizeSecurityObject($tokenOrUser): array
    {
        return [
            'class' => get_class($tokenOrUser),
            'user_identifier' => $this->hideEmailAddress($this->readUserIdentifier($tokenOrUser)),
        ];
    }

    private function readUserIdentifier($tokenOrUser): ?string
    {
        try {
            if ($this->holdsUninitializedProxyUser($tokenOrUser)) {
                return null;
            }

            $userIdentifier = method_exists($tokenOrUser, 'getUserIdentifier')
                ? $tokenOrUser->getUserIdentifier()
                : $tokenOrUser->getUsername();

            return $userIdentifier === '' ? null : $userIdentifier;
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function holdsUninitializedProxyUser($tokenOrUser): bool
    {
        return is_a($tokenOrUser, 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            && $this->isUninitializedProxy($tokenOrUser->getUser());
    }

    private function hideEmailAddress(?string $userIdentifier): ?string
    {
        if ($userIdentifier !== null && strpos($userIdentifier, '@') !== false) {
            return '[email]';
        }

        return $userIdentifier;
    }

    private function isProxy($data): bool
    {
        return $data instanceof Proxy || is_a($data, 'Doctrine\Common\Persistence\Proxy');
    }

    private function isUninitializedProxy($data): bool
    {
        return $this->isProxy($data) && !$data->__isInitialized();
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
        if (method_exists(Utils::class, 'jsonEncode')) {
            return Utils::jsonEncode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                $ignoreErrors
            );
        }

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($ignoreErrors ? 0 : JSON_THROW_ON_ERROR)
        );
    }
}
