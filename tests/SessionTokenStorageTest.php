<?php

declare(strict_types=1);

namespace Solventt\Csrf\Tests;

use PHPUnit\Framework\TestCase;
use Solventt\Csrf\SessionTokenStorage;

class SessionTokenStorageTest extends TestCase
{
    private const TOKEN_NAME = '_csrf';
    private SessionTokenStorage $storage;

    protected function setUp(): void
    {
        $_SESSION[self::TOKEN_NAME] = 'value';
        $this->storage = new SessionTokenStorage();
    }

    public function testGetMethod()
    {
        self::assertSame('value', $this->storage->get(self::TOKEN_NAME));
    }

    public function testSetMethod()
    {
        $this->storage->set('customName', 'otherValue');

        self::assertSame('otherValue', $this->storage->get('customName'));
    }

    public function testRemoveMethod()
    {
        $this->storage->remove(self::TOKEN_NAME);

        self::assertNull($this->storage->get(self::TOKEN_NAME));
    }
}