<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Service;

/**
 * Extracts the short headline of an exception-shaped log message.
 *
 * Ported from the canonical ExceptionMessageParser in evp/lib-application-logging-bundle (8.9.1/7.9.1)
 * and intentionally kept identical, so both bundles split messages the same way. "Exception" matches
 * capitalized or lowercase, and the split is anchored at the first " in /" file path — not at the
 * first word "in" — so tails like "in state NEW", "in driver: ..." or SQL "IN (...)" are kept in the
 * headline. The delimiter stays case-sensitive, so "IN /..." fragments are not split points.
 */
class ExceptionMessageParser
{
    private const PATTERN = '/^(.*?[Ee]xception.*?) in \//';

    public function parse(string $message): ?string
    {
        if (preg_match(self::PATTERN, strtr($message, ["\r" => '', "\n" => ' ']), $matches)) {
            return $matches[1];
        }

        return null;
    }
}
