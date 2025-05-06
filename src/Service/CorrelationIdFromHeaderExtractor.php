<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;
use Symfony\Component\HttpFoundation\RequestStack;

class CorrelationIdFromHeaderExtractor
{
    private $requestStack;

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    public function getCorrelationId(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        return $request->headers->get(CorrelationIdListener::HEADER_NAME);
    }
}
