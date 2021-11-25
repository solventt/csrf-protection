<?php

declare(strict_types=1);

namespace Solventt\Csrf\Tests;

use Solventt\Csrf\Interfaces\CsrfTokenInterface;
use Solventt\Csrf\MaskedCsrfToken;
use Solventt\Csrf\SecurityHelper;
use Solventt\Csrf\SessionTokenStorage;

class MaskedCsrfTokenTest extends TestCase
{
    private MaskedCsrfToken $csrfToken;

    protected function setUp(): void
    {
        $this->csrfToken = new MaskedCsrfToken(new SessionTokenStorage(), new SecurityHelper());
    }

    public function testGetNameMethod()
    {
        self::assertSame(CsrfTokenInterface::DEFAULT_NAME, $this->csrfToken->getName());
    }

    public function testEqualsMethod()
    {
        $maskedToken = $this->csrfToken->getValue();

        self::assertTrue($this->csrfToken->equals($maskedToken));
    }

    public function testGetValueMethod()
    {
        $tokenOne = $this->csrfToken->getValue(false);
        $maskedToken = $this->csrfToken->getValue();

        self::assertNotSame($tokenOne, $maskedToken);

        $tokenTwo = $this->csrfToken->getValue(false);

        self::assertSame($tokenOne, $tokenTwo);

        unset($_SESSION[CsrfTokenInterface::DEFAULT_NAME]);

        $tokenThree = $this->csrfToken->getValue(false);

        self::assertNotSame($tokenOne, $tokenThree);
    }

    public function testRegenerateMethod()
    {
        $tokenOne = $this->csrfToken->getValue(false);

        $this->csrfToken->regenerate();

        $tokenTwo = $this->csrfToken->getValue(false);

        self::assertNotSame($tokenOne, $tokenTwo);
    }

    public function testCustomTokenName()
    {
        $token = new MaskedCsrfToken(
            new SessionTokenStorage(),
            new SecurityHelper(),
            $customTokenName = 'customValue'
        );

        self::assertInaccessiblePropertySame($customTokenName,$token, 'name');
    }
}