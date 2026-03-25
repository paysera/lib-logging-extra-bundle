<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Paysera\LoggingExtraBundle\Service\Formatter\FormatterTrait;

class FormatterTraitTest extends TestCase
{
    /**
     * @dataProvider sensitiveKeysProvider
     */
    public function testSensitiveKeysAreRedacted(string $propertyName)
    {
        $object = new \stdClass();
        $object->$propertyName = 'sensitive_value';
        $object->name = 'visible';

        $result = $this->normalizeObject($object);

        $this->assertSame('***', $result[$propertyName]);
        $this->assertSame('visible', $result['name']);
    }

    public function sensitiveKeysProvider(): array
    {
        return [
            'password' => ['password'],
            'secret' => ['secret'],
            'apiKey' => ['apiKey'],
            'apiSecret' => ['apiSecret'],
            'secretKey' => ['secretKey'],
            'credentials' => ['credentials'],
        ];
    }

    /**
     * @dataProvider caseInsensitiveProvider
     */
    public function testSensitiveKeysAreCaseInsensitive(string $propertyName)
    {
        $object = new \stdClass();
        $object->$propertyName = 'sensitive_value';

        $result = $this->normalizeObject($object);

        $this->assertSame('***', $result[$propertyName]);
    }

    public function caseInsensitiveProvider(): array
    {
        return [
            'Password' => ['Password'],
            'PASSWORD' => ['PASSWORD'],
            'pAsSwOrD' => ['pAsSwOrD'],
            'SECRET' => ['SECRET'],
            'ApiKey' => ['ApiKey'],
            'APIKEY' => ['APIKEY'],
            'Credentials' => ['Credentials'],
        ];
    }

    /**
     * @dataProvider separatorVariantsProvider
     */
    public function testSensitiveKeysWithSeparators(string $propertyName)
    {
        $object = new \stdClass();
        $object->$propertyName = 'sensitive_value';

        $result = $this->normalizeObject($object);

        $this->assertSame('***', $result[$propertyName]);
    }

    public function separatorVariantsProvider(): array
    {
        return [
            'api_key' => ['api_key'],
            'api-key' => ['api-key'],
            'api.key' => ['api.key'],
            'api_secret' => ['api_secret'],
            'api-secret' => ['api-secret'],
            'secret_key' => ['secret_key'],
            'secret-key' => ['secret-key'],
            'api_secret_key' => ['api_secret_key'],
            'api-secret-key' => ['api-secret-key'],
            'API_KEY' => ['API_KEY'],
            'API_SECRET' => ['API_SECRET'],
            'SECRET_KEY' => ['SECRET_KEY'],
        ];
    }

    public function testNonSensitivePropertiesPassThrough()
    {
        $object = new \stdClass();
        $object->name = 'John';
        $object->email = 'john@example.com';
        $object->age = 30;
        $object->active = true;

        $result = $this->normalizeObject($object);

        $this->assertSame('John', $result['name']);
        $this->assertSame('john@example.com', $result['email']);
        $this->assertSame(30, $result['age']);
        $this->assertTrue($result['active']);
    }

    public function testDoubleUnderscorePrefixedPropertiesAreExcluded()
    {
        $object = new \stdClass();
        $object->__internal = 'hidden';
        $object->name = 'visible';

        $result = $this->normalizeObject($object);

        $this->assertArrayNotHasKey('__internal', $result);
        $this->assertSame('visible', $result['name']);
    }

    public function testMixedSensitiveAndNonSensitiveProperties()
    {
        $object = new \stdClass();
        $object->username = 'admin';
        $object->password = 'super_secret';
        $object->email = 'admin@example.com';
        $object->apiKey = 'key-123';
        $object->role = 'admin';
        $object->credentials = ['token' => 'abc'];

        $result = $this->normalizeObject($object);

        $this->assertSame('admin', $result['username']);
        $this->assertSame('***', $result['password']);
        $this->assertSame('admin@example.com', $result['email']);
        $this->assertSame('***', $result['apiKey']);
        $this->assertSame('admin', $result['role']);
        $this->assertSame('***', $result['credentials']);
    }

    private function normalizeObject(object $data): array
    {
        $formatter = new class {
            use FormatterTrait;

            public function callNormalizeObject(object $data): array
            {
                return $this->normalizeObject($data);
            }
        };

        return $formatter->callNormalizeObject($data);
    }
}
