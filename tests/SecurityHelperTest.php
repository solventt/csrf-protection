<?php

declare(strict_types=1);

namespace Solventt\Csrf\Tests;

use LengthException;
use Solventt\Csrf\SecurityHelper;

class SecurityHelperTest extends TestCase
{
    private SecurityHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new SecurityHelper();
    }

    public function testGenerateToken()
    {
        $token = $this->helper->generateToken();
        self::assertSame(32, mb_strlen($token));

        $token = $this->helper->generateToken(15);
        self::assertSame(15, mb_strlen($token));
    }

    public function testLengthExceptionWhenGenerateToken()
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('The length of the token cannot be less than 15 symbols');

        $this->helper->generateToken(14);
    }

    public function testTokenMasking()
    {
        $token = $this->helper->generateToken();

        $maskedToken = $this->helper->addMask($token);

        self::assertNotSame($token, $maskedToken);

        $unmaskedToken = $this->helper->removeMask($maskedToken);

        self::assertSame($token, $unmaskedToken);
    }

    public function testBase64Encode()
    {
        $encoded = self::invokeInaccessibleMethod(
            $this->helper,
            'base64Encode',
            random_bytes(16)
        );

        self::assertStringNotContainsString('+', $encoded);
        self::assertStringNotContainsString('/', $encoded);
        self::assertStringEndsNotWith('=', $encoded);
    }

    public function testBase64Decode()
    {
        $encoded = self::invokeInaccessibleMethod(
            $this->helper,
            'base64Encode',
            $random = random_bytes(16)
        );

        $decoded = self::invokeInaccessibleMethod(
            $this->helper,
            'base64Decode',
            $encoded
        );

        self::assertSame($random, $decoded);
    }
}