<?php

declare(strict_types=1);

namespace Solventt\Csrf\Interfaces;

interface SecurityInterface
{
    /**
     * Generates a cryptographically secure value
     *
     * @param  int $length a token length
     * @return string
     */
    public function generateToken(int $length): string;

    /**
     * Applies a random mask to the CSRF token making it unique when its requested
     *
     * @param  string $token an unmasked token
     * @return string
     */
    public function addMask(string $token): string;

    /**
     * Removes the mask from the CSRF token previously masked with the 'addMask' method
     *
     * @param  string $token a masked token
     * @return string
     */
    public function removeMask(string $token): string;
}