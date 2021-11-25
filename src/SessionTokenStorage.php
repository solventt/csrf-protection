<?php

declare(strict_types=1);

namespace Solventt\Csrf;

use Solventt\Csrf\Interfaces\TokenStorageInterface;

class SessionTokenStorage implements TokenStorageInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $tokenName): ?string
    {
        /** @var mixed $value */
        $value = $_SESSION[$tokenName] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $tokenName, string $value): void
    {
        $_SESSION[$tokenName] = $value;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $tokenName): void
    {
        unset($_SESSION[$tokenName]);
    }
}