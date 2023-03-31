<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function index(): Response
    {
        return new Response();
    }
}
