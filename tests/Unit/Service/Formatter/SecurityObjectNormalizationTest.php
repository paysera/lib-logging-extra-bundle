<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit\Service\Formatter;

use DateTimeImmutable;
use Gelf\Message;
use Monolog\Logger;
use Paysera\LoggingExtraBundle\Service\Formatter\GelfMessageFormatter;
use Paysera\LoggingExtraBundle\Service\Formatter\LineFormatter;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\PlainObject;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\AccountUser;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\BrokenToken;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\ProxyUser;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\Token;
use Paysera\LoggingExtraBundle\Tests\Unit\Fixtures\Security\User;
use PHPUnit\Framework\TestCase;

class SecurityObjectNormalizationTest extends TestCase
{
    private const USER_IDENTIFIER = 'api-client';
    private const EMAIL_ADDRESS = 'api.client@example.com';
    private const USER_PASSWORD = 'fixture-user-password';
    private const TOKEN_CREDENTIALS = 'fixture-token-credentials';
    private const TOKEN_ATTRIBUTE = 'fixture-token-attribute';
    private const PROXY_USER_ID = 987654321;
    private const MESSAGE = 'Authenticator successful!';

    /**
     * @dataProvider gelfContextProvider
     */
    public function testGelfContextKeepsOnlyClassAndUserIdentifierOfSecurityObjects(
        array $context,
        array $expectedContextFields
    ): void {
        $message = (new GelfMessageFormatter('app'))->format($this->buildRecord($context));
        $this->assertInstanceOf(Message::class, $message);

        $contextFields = [];
        foreach (array_keys($context) as $key) {
            $contextFields[$key] = json_decode($message->getAdditional('ctxt_' . $key), true);
        }

        $this->assertSame($expectedContextFields, $contextFields);
    }

    public function gelfContextProvider(): array
    {
        return [
            'Token is written as its class and the user identifier' => [
                'context' => ['token' => $this->buildToken(self::USER_IDENTIFIER)],
                'expectedContextFields' => [
                    'token' => ['class' => Token::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'User is written as its class and the user identifier' => [
                'context' => ['user' => new User(self::USER_IDENTIFIER, self::USER_PASSWORD, ['ROLE_API'])],
                'expectedContextFields' => [
                    'user' => ['class' => User::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'Token in a list is written as its class and the user identifier' => [
                'context' => ['tokens' => [$this->buildToken(self::USER_IDENTIFIER)]],
                'expectedContextFields' => [
                    'tokens' => [['class' => Token::class, 'user_identifier' => self::USER_IDENTIFIER]],
                ],
            ],
            'User identifier holding an e-mail address is written as the e-mail marker' => [
                'context' => ['token' => $this->buildToken(self::EMAIL_ADDRESS)],
                'expectedContextFields' => [
                    'token' => ['class' => Token::class, 'user_identifier' => '[email]'],
                ],
            ],
            'Token whose identifier getter throws is written without an identifier' => [
                'context' => ['token' => new BrokenToken(self::TOKEN_CREDENTIALS, ['ROLE_API'])],
                'expectedContextFields' => [
                    'token' => ['class' => BrokenToken::class, 'user_identifier' => null],
                ],
            ],
            'Token with no user is written without a user identifier' => [
                'context' => ['token' => new Token(self::TOKEN_CREDENTIALS, ['ROLE_API'])],
                'expectedContextFields' => [
                    'token' => ['class' => Token::class, 'user_identifier' => null],
                ],
            ],
            'Uninitialized proxy user is written as its id only' => [
                'context' => [
                    'user' => new ProxyUser(self::PROXY_USER_ID, false, self::USER_IDENTIFIER, self::USER_PASSWORD),
                ],
                'expectedContextFields' => ['user' => ['id' => self::PROXY_USER_ID]],
            ],
            'Initialized proxy user is written as its class and the user identifier' => [
                'context' => [
                    'user' => new ProxyUser(self::PROXY_USER_ID, true, self::USER_IDENTIFIER, self::USER_PASSWORD),
                ],
                'expectedContextFields' => [
                    'user' => ['class' => ProxyUser::class, 'user_identifier' => self::USER_IDENTIFIER],
                ],
            ],
            'Plain object keeps all its properties' => [
                'context' => ['plain' => new PlainObject('visible-name', 7)],
                'expectedContextFields' => ['plain' => ['count' => 7, 'name' => 'visible-name']],
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

        $message = (new GelfMessageFormatter('app'))->format($this->buildRecord(['token' => $token]));

        $this->assertSame(
            [
                'token' => ['class' => Token::class, 'user_identifier' => null],
                'userLoaded' => false,
            ],
            [
                'token' => json_decode($message->getAdditional('ctxt_token'), true),
                'userLoaded' => $user->__isInitialized(),
            ]
        );
    }

    public function testUserWithItsOwnUninitializedProxyGetterKeepsItsIdentifier(): void
    {
        $account = new ProxyUser(self::PROXY_USER_ID, false, 'account', self::USER_PASSWORD);
        $user = new AccountUser(self::USER_IDENTIFIER, self::USER_PASSWORD, $account);

        $message = (new GelfMessageFormatter('app'))->format($this->buildRecord(['user' => $user]));

        $this->assertSame(
            ['class' => AccountUser::class, 'user_identifier' => self::USER_IDENTIFIER],
            json_decode($message->getAdditional('ctxt_user'), true)
        );
    }

    private function buildToken(string $userIdentifier): Token
    {
        $token = new Token(self::TOKEN_CREDENTIALS, ['ROLE_API']);
        $token->setUser(new User($userIdentifier, self::USER_PASSWORD, ['ROLE_API']));
        $token->setAttribute('signature', self::TOKEN_ATTRIBUTE);

        return $token;
    }

    private function buildRecord(array $context): array
    {
        return [
            'message' => self::MESSAGE,
            'context' => $context,
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'security',
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];
    }

    private function format(string $formatterName, array $context): string
    {
        $record = $this->buildRecord($context);

        if ($formatterName === 'gelf') {
            $message = (new GelfMessageFormatter('app'))->format($record);
            $this->assertInstanceOf(Message::class, $message);

            return (string) json_encode($message->toArray());
        }

        return (new LineFormatter())->format($record);
    }
}
