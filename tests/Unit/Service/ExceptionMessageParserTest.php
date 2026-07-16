<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service;

use Paysera\LoggingExtraBundle\Service\ExceptionMessageParser;
use PHPUnit\Framework\TestCase;

class ExceptionMessageParserTest extends TestCase
{
    /**
     * @dataProvider parseProvider
     */
    public function testParseReturnsShortHeadlineOrNull(string $message, ?string $expected): void
    {
        $this->assertSame($expected, (new ExceptionMessageParser())->parse($message));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function parseProvider(): array
    {
        return [
            'uncaught exception with class name and reason' => [
                'Uncaught RuntimeException: error in /app/file.php:42',
                'Uncaught RuntimeException: error',
            ],
            'namespaced exception class' => [
                'App\Exception\SomeException: error in /app/file.php:42',
                'App\Exception\SomeException: error',
            ],
            'class name directly before the location' => [
                'LogicException in /app/file.php:42',
                'LogicException',
            ],
            'driver exception carrying a bracketed state' => [
                'PDOException: SQLSTATE[23000] in /app/file.php:42',
                'PDOException: SQLSTATE[23000]',
            ],
            'symfony uncaught php exception prefix' => [
                'Uncaught PHP Exception RuntimeException in /app/file.php:42',
                'Uncaught PHP Exception RuntimeException',
            ],
            'lowercase natural language exception' => [
                'An exception occurred while executing a query in /app/vendor/doctrine/x.php:1',
                'An exception occurred while executing a query',
            ],
            'exception with file and line location' => [
                'InvalidArgumentException: msg in /path/file.php line 5',
                'InvalidArgumentException: msg',
            ],
            'word in inside the reason is not a split point' => [
                'Uncaught RuntimeException: Cannot process transfer in state NEW in /app/src/Foo.php:10',
                'Uncaught RuntimeException: Cannot process transfer in state NEW',
            ],
            'sql in operator before the file path is kept' => [
                "An exception occurred while executing 'SELECT * FROM t WHERE id IN (1,2)'"
                    . ' in /app/vendor/doctrine/x.php:1',
                "An exception occurred while executing 'SELECT * FROM t WHERE id IN (1,2)'",
            ],
            'sql inline hint with uppercase IN before the file path is kept' => [
                "An exception occurred while executing 'SELECT * FROM t WHERE id IN /*hint*/ (1,2)'"
                    . ' in /app/doctrine.php:1',
                "An exception occurred while executing 'SELECT * FROM t WHERE id IN /*hint*/ (1,2)'",
            ],
            'uppercase IN followed by a path value is not a split point' => [
                'RuntimeException: path IN /etc was blocked in /app/src/Foo.php:10',
                'RuntimeException: path IN /etc was blocked',
            ],
            'chained exception is cut at the first file path' => [
                "LogicException: inner failure in /app/src/B.php:5\nStack trace:\n#0 {main}\n\n"
                    . "Next RuntimeException: outer failure in /app/src/A.php:20\nStack trace:\n#0 {main}",
                'LogicException: inner failure',
            ],
            'driver exception without a file path is not matched' => [
                'An exception occurred in driver: SQLSTATE[HY000] [2006] MySQL server has gone away',
                null,
            ],
            'symfony one-liner with at file line trailer is not matched' => [
                'Uncaught PHP Exception Symfony\Component\HttpKernel\Exception\NotFoundHttpException: '
                    . '"No route found" at /app/vendor/RouterListener.php line 136',
                null,
            ],
            'non-exception message mentioning an exception class is not matched' => [
                'Notified ExceptionListener about failure in production',
                null,
            ],
            'exception message without a file path is not matched' => [
                'Some exception occurred without any location',
                null,
            ],
            'newline is normalized to a space before matching' => [
                "Some exception\n stack in /app/x.php:1",
                'Some exception  stack',
            ],
            'carriage return is stripped before matching' => [
                "Some exception\r\n stack in /app/x.php:1",
                'Some exception  stack',
            ],
            'plain message is not exception-shaped' => [
                'Example message',
                null,
            ],
            'location without an exception is not matched' => [
                'User logged in /app/x.php',
                null,
            ],
            'exception appearing only in the file path is not matched' => [
                'Some error in /app/Exception/Foo.php:42',
                null,
            ],
        ];
    }
}
