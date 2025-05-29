<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service\CorrelationIdProvider;

use Symfony\Component\HttpFoundation\RequestStack;
use Paysera\LoggingExtraBundle\Listener\CorrelationIdListener;

/**
 * @internal
 */
class RequestHeaderCorrelationIdProvider
{
    private $requestStack;
    private $correlationId;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->correlationId = null;
    }

    public function getCorrelationId(): ?string
    {
        if ($this->correlationId !== null) {
            return $this->correlationId;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $this->correlationId = $request->headers->get(CorrelationIdListener::HEADER_NAME);

        return $this->correlationId;
    }

    public function reset(): void
    {
        $this->correlationId = null;
    }
}
