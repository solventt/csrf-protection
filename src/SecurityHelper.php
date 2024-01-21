<?php

declare(strict_types=1);

namespace Solventt\Csrf;

use LengthException;
use Solventt\Csrf\Interfaces\SecurityInterface;

class SecurityHelper implements SecurityInterface
{
    /**
     * @inheritDoc
     * @throws LengthException
     */
    public function generateToken(int $length = 32): string
    {
        if ($length < 15) {
            throw new LengthException('The length of the token cannot be less than 15 symbols');
        }

        // Base64-encoded data takes about 30% more space than the original data
        $bytes = random_bytes((int) ceil($length * 0.75));

        return mb_substr(self::base64Encode($bytes), 0, $length);
    }

    /**
     * @inheritDoc
     */
    public function addMask(string $token): string
    {
        /** @psalm-suppress InvalidArgument $mask */
        $mask = random_bytes(strlen($token));

        $token = $mask ^ $token;

        return self::base64Encode($mask . $token);
    }

    /**
     * @inheritDoc
     */
    public function removeMask(string $token): string
    {
        $raw = self::base64Decode($token);

        $mask = substr($raw, 0, $length = (int) (strlen($raw) / 2));
        $mixedToken = substr($raw, $length);

        return $mask ^ $mixedToken;
    }

    /**
     * @param  string $input
     * @return string
     */
    private static function base64Encode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * @param  string $input
     * @return string
     */
    private static function base64Decode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}