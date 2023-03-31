<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Service;

use Sentry\Options;
use Sentry\Transport\TransportFactoryInterface;
use Sentry\Transport\TransportInterface;

class TestTransportFactory implements TransportFactoryInterface
{
    public function __construct(private TransportInterface $transport)
    {
    }

    public function create(Options $options): TransportInterface
    {
        return $this->transport;
    }
}
