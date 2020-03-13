<?php

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function index()
    {
        return new Response();
    }
}
