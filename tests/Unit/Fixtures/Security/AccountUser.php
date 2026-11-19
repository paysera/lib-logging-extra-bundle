<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security;

class AccountUser extends User
{
    /**
     * @var ProxyUser
     */
    private $account;

    public function __construct(string $userIdentifier, string $password, ProxyUser $account)
    {
        parent::__construct($userIdentifier, $password, ['ROLE_USER']);
        $this->account = $account;
    }

    public function getUser(): ProxyUser
    {
        return $this->account;
    }
}
