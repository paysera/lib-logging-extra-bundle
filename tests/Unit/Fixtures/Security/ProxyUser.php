<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security;

use Doctrine\Persistence\Proxy;

class ProxyUser extends User implements Proxy
{
    private int $id;
    private bool $initialized;

    public function __construct(int $id, bool $initialized, string $userIdentifier, string $password)
    {
        parent::__construct($userIdentifier, $password, ['ROLE_USER']);
        $this->id = $id;
        $this->initialized = $initialized;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        $this->__load();

        return parent::getUserIdentifier();
    }

    public function getUsername(): string
    {
        $this->__load();

        return parent::getUsername();
    }

    public function __load(): void
    {
        $this->initialized = true;
    }

    public function __isInitialized(): bool
    {
        return $this->initialized;
    }
}
