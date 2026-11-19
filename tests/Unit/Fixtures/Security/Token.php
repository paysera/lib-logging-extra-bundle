<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class Token extends AbstractToken
{
    /**
     * @var string
     */
    private $signingKey;

    /**
     * @param string[] $roles
     */
    public function __construct(string $signingKey, array $roles)
    {
        parent::__construct($roles);
        $this->signingKey = $signingKey;
    }

    public function getCredentials()
    {
        return $this->signingKey;
    }
}
