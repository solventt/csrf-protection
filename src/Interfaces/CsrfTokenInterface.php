<?php

declare(strict_types=1);

namespace Solventt\Csrf\Interfaces;

interface CsrfTokenInterface
{
    public const DEFAULT_NAME = '_csrf';

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string an actual token value
     */
    public function getValue(): string;

    /**
     * Compares the token from the request with the token found in a token storage
     *
     * @param  string $requestToken a token value obtained from the request
     * @return bool
     */
    public function equals(string $requestToken): bool;
}