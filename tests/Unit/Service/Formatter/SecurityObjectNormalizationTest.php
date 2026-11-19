<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Formatter;

use Gelf\Message;
use Paysera\LoggingExtraBundle\Service\ExceptionMessageParser;
use Paysera\LoggingExtraBundle\Service\Formatter\GelfMessageFormatter;
use Paysera\LoggingExtraBundle\Service\Formatter\LineFormatter;
use Paysera\LoggingExtraBundle\Service\Formatter\StdoutJsonFormatter;
use Paysera\LoggingExtraBundle\Service\Formatter\StdoutRecordEncoder;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\PlainObject;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\AccountUser;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\BrokenToken;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\ProxyUser;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\Token;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\User;
use Paysera\LoggingExtraBundle\Tests\Unit\Support\MonologRecordTrait;
use PHPUnit\Framework\TestCase;

class SecurityObjectNormalizationTest extends TestCase
{
    use MonologRecordTrait;

    private const USER_IDENTIFIER = 'api-client';
    private const EMAIL_ADDRESS = 'api.client@example.com';
    private const USER_PASSWORD = 'fixture-user-pass-value';
    private const TOKEN_CREDENTIALS = 'fixture-token-credential-value';
    private const TOKEN_ATTRIBUTE = 'fixture-token-attribute';
    private const PROXY_USER_ID = 987654321;
    private const MESSAGE = 'Authenticator successful!';

    /**
     * @dataProvider stdoutContextProvider
     */
    public function testStdoutContextKeepsOnlyClassAndUserIdentifierOfSecurityObjects(
        array $context,
        array $expectedContext
    ): void {
        $formatter = new StdoutJsonFormatter(new StdoutRecordEncoder('app', new ExceptionMessageParser()));

        $decoded = json_decode($formatter->format($this->buildLogRecord(['context' => $context])), true);

        $this->assertSame($expectedContext, $decoded['context']);
    }

    public function stdoutContextProvider(): array
    {
        return [
            'Token is written as its class and the user identifier' => [
                'context' => ['token' => $this->buildToken(self::USER_IDENTIFIER)],
                'expectedContext' => [
                    'token' => ['class' => Token::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'User is written as its class and the user identifier' => [
                'context' => ['user' => new User(self::USER_IDENTIFIER, self::USER_PASSWORD, ['ROLE_API'])],
                'expectedContext' => [
                    'user' => ['class' => User::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'Token nested in an array is written as its class and the user identifier' => [
                'context' => ['authentication' => ['tokens' => [$this->buildToken(self::USER_IDENTIFIER)]]],
                'expectedContext' => [
                    'authentication' => [
                        'tokens' => [['class' => Token::class, 'user_identifier' => self::USER_IDENTIFIER]],
                    ],
                ],
            ],
            'User identifier holding an e-mail address is written as the e-mail marker' => [
                'context' => ['token' => $this->buildToken(self::EMAIL_ADDRESS)],
                'expectedContext' => [
                    'token' => ['class' => Token::class, 'user_identifier' => '[email]'],
                ],
            ],
            'Token whose identifier getter throws is written without an identifier' => [
                'context' => ['token' => new BrokenToken(self::TOKEN_CREDENTIALS, ['ROLE_API'])],
                'expectedContext' => [
                    'token' => ['class' => BrokenToken::class, 'user_identifier' => null],
                ],
            ],
            'Token with no user is written without a user identifier' => [
                'context' => ['token' => new Token(self::TOKEN_CREDENTIALS, ['ROLE_API'])],
                'expectedContext' => [
                    'token' => ['class' => Token::class, 'user_identifier' => null],
                ],
            ],
            'Uninitialized proxy user is written as its id only' => [
                'context' => [
                    'user' => new ProxyUser(self::PROXY_USER_ID, false, self::USER_IDENTIFIER, self::USER_PASSWORD),
                ],
                'expectedContext' => ['user' => ['id' => self::PROXY_USER_ID]],
            ],
            'Initialized proxy user is written as its class and the user identifier' => [
                'context' => [
                    'user' => new ProxyUser(self::PROXY_USER_ID, true, self::USER_IDENTIFIER, self::USER_PASSWORD),
                ],
                'expectedContext' => [
                    'user' => ['class' => ProxyUser::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'Plain object keeps all its properties' => [
                'context' => ['plain' => new PlainObject('visible-name', 7)],
                'expectedContext' => ['plain' => ['count' => 7, 'name' => 'visible-name']],
            ],
        ];
    }

    /**
     * @dataProvider secretBearingContextProvider
     */
    public function testFormattedRecordWritesTheEmailMarkerInsteadOfSecrets(string $formatterName, array $context): void
    {
        $output = $this->format($formatterName, $context);

        $this->assertSame(
            ['leakedValues' => [], 'holdsEmailMarker' => true],
            [
                'leakedValues' => array_values(array_filter(
                    [self::USER_PASSWORD, self::TOKEN_CREDENTIALS, self::TOKEN_ATTRIBUTE, self::EMAIL_ADDRESS],
                    static function (string $secret) use ($output): bool {
                        return strpos($output, $secret) !== false;
                    }
                )),
                'holdsEmailMarker' => strpos($output, '[email]') !== false,
            ]
        );
    }

    public function secretBearingContextProvider(): array
    {
        $token = ['token' => $this->buildToken(self::EMAIL_ADDRESS)];
        $user = ['user' => new User(self::EMAIL_ADDRESS, self::USER_PASSWORD, ['ROLE_API'])];
        $tokenInList = ['tokens' => [$this->buildToken(self::EMAIL_ADDRESS)]];

        return [
            'Stdout JSON formatter writes a token as the e-mail marker' => ['stdout json', $token],
            'Stdout JSON formatter writes a user as the e-mail marker' => ['stdout json', $user],
            'Stdout JSON formatter writes a token in a list as the e-mail marker' => ['stdout json', $tokenInList],
            'GELF formatter writes a token as the e-mail marker' => ['gelf', $token],
            'GELF formatter writes a user as the e-mail marker' => ['gelf', $user],
            'GELF formatter writes a token in a list as the e-mail marker' => ['gelf', $tokenInList],
            'Line formatter writes a token as the e-mail marker' => ['line', $token],
            'Line formatter writes a user as the e-mail marker' => ['line', $user],
            'Line formatter writes a token in a list as the e-mail marker' => ['line', $tokenInList],
        ];
    }

    public function testTokenWithUninitializedProxyUserIsWrittenWithoutLoadingTheUser(): void
    {
        $user = new ProxyUser(self::PROXY_USER_ID, false, self::USER_IDENTIFIER, self::USER_PASSWORD);
        $token = new Token(self::TOKEN_CREDENTIALS, ['ROLE_API']);
        $token->setUser($user);
        $formatter = new StdoutJsonFormatter(new StdoutRecordEncoder('app', new ExceptionMessageParser()));

        $decoded = json_decode($formatter->format($this->buildLogRecord(['context' => ['token' => $token]])), true);

        $this->assertSame(
            [
                'context' => ['token' => ['class' => get_class($token), 'user_identifier' => null]],
                'userLoaded' => false,
            ],
            ['context' => $decoded['context'], 'userLoaded' => $user->__isInitialized()]
        );
    }

    public function testUserWithItsOwnUninitializedProxyGetterKeepsItsIdentifier(): void
    {
        $account = new ProxyUser(self::PROXY_USER_ID, false, 'account', self::USER_PASSWORD);
        $user = new AccountUser(self::USER_IDENTIFIER, self::USER_PASSWORD, $account);
        $formatter = new StdoutJsonFormatter(new StdoutRecordEncoder('app', new ExceptionMessageParser()));

        $decoded = json_decode($formatter->format($this->buildLogRecord(['context' => ['user' => $user]])), true);

        $this->assertSame(
            ['user' => ['class' => AccountUser::class, 'user_identifier' => self::USER_IDENTIFIER]],
            $decoded['context']
        );
    }

    private function buildToken(string $userIdentifier): Token
    {
        $token = new Token(self::TOKEN_CREDENTIALS, ['ROLE_API']);
        $token->setUser(new User($userIdentifier, self::USER_PASSWORD, ['ROLE_API']));
        $token->setAttribute('signature', self::TOKEN_ATTRIBUTE);

        return $token;
    }

    private function format(string $formatterName, array $context): string
    {
        $record = $this->buildLogRecord(['message' => self::MESSAGE, 'context' => $context]);

        if ($formatterName === 'stdout json') {
            return (new StdoutJsonFormatter(new StdoutRecordEncoder('app', new ExceptionMessageParser())))
                ->format($record);
        }

        if ($formatterName === 'gelf') {
            $message = (new GelfMessageFormatter('app'))->format($record);
            $this->assertInstanceOf(Message::class, $message);

            return (string) json_encode($message->toArray());
        }

        return (new LineFormatter())->format($record);
    }
}
