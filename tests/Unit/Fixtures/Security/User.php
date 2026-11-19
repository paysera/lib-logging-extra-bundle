<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    /**
     * @var string
     */
    private $userIdentifier;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string[]
     */
    private $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(string $userIdentifier, string $password, array $roles)
    {
        $this->userIdentifier = $userIdentifier;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->userIdentifier;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function eraseCredentials(): void
    {
    }
}
