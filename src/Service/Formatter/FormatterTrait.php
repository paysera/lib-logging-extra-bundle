<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\Formatter;

use DateTimeInterface;
use Doctrine\Common\Persistence\Proxy as LegacyProxy;
use Doctrine\Persistence\Proxy;
use Doctrine\ORM\PersistentCollection;
use Monolog\Utils;
use Throwable;

trait FormatterTrait
{
    protected function normalize($data, $depth = 0)
    {
        $prenormalizedData = $this->prenormalizeData($data, $depth);

        return parent::normalize($prenormalizedData, $depth);
    }

    private function prenormalizeData($data, $depth)
    {
        if (
            is_a($data, 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            || (
                is_a($data, 'Symfony\Component\Security\Core\User\UserInterface')
                && !$this->isUninitializedProxy($data)
            )
        ) {
            return $this->normalizeSecurityObject($data);
        }

        if ($depth > 2) {
            return $this->getScalarRepresentation($data);
        }

        if ($data instanceof PersistentCollection) {
            return $data->isInitialized() ? iterator_to_array($data) : get_class($data);
        }

        if ($this->isProxy($data)) {
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
        return method_exists($tokenOrUser, 'getUser') && $this->isUninitializedProxy($tokenOrUser->getUser());
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
        return $data instanceof Proxy || $data instanceof LegacyProxy;
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
        return Utils::jsonEncode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            $ignoreErrors
        );
    }
}
