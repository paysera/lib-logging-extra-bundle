<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

/**
 * Holds the request-spanning trace id for the current process. The value is the
 * `Paysera-Trace-Id` header the public gateway stamps on every inbound request
 * (see TraceIdListener), so the bundle wires this up itself — a host service needs
 * no code to get `trace_id` into its logs, only to bump the library.
 */
class TraceIdProvider
{
    /**
     * Upper bound on the accepted value length. The gateway's trace ids are far
     * shorter; the cap keeps a hostile client from bloating every log line and
     * Sentry event, and stays within Sentry's 200-char tag limit (trace_id is
     * promoted to a Sentry tag).
     */
    private const MAX_LENGTH = 200;

    /**
     * Charset a legitimate trace id may use — covers the UUID / hex / opaque-id
     * shapes the gateway emits. Anything outside it (control characters, whitespace,
     * structural punctuation) is rejected so it cannot corrupt downstream log sinks.
     * `\z` (not `$`) anchors strictly so a trailing newline cannot slip through.
     */
    private const PATTERN = '/^[A-Za-z0-9._-]+\z/';

    private ?string $traceId;

    public function __construct()
    {
        $this->traceId = null;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * The value is client-controlled (an incoming gateway header), so it is validated
     * here — the single place the invariant is enforced for every caller. A malformed
     * value is ignored rather than propagated to the log sinks.
     */
    public function setTraceId(string $traceId): void
    {
        if (!self::isValid($traceId)) {
            return;
        }

        $this->traceId = $traceId;
    }

    public function resetTraceId(): void
    {
        $this->traceId = null;
    }

    private static function isValid(string $traceId): bool
    {
        return $traceId !== ''
            && strlen($traceId) <= self::MAX_LENGTH
            && preg_match(self::PATTERN, $traceId) === 1;
    }
}
