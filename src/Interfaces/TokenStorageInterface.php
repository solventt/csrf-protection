<?php

declare(strict_types=1);

namespace Solventt\Csrf\Interfaces;

interface TokenStorageInterface
{
    /**
     * Takes the CSRF token out of a storage
     *
     * @param  string $tokenName
     * @return string|null
     */
    public function get(string $tokenName): ?string;

    /**
     * Puts the CSRF token into a storage
     *
     * @param string $tokenName
     * @param string $value
     */
    public function set(string $tokenName, string $value): void;

    /**
     * Removes the CSRF token from a storage by its name
     *
     * @param string $tokenName
     */
    public function remove(string $tokenName): void;
}