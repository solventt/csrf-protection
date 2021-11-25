<?php

declare(strict_types=1);

namespace Solventt\Csrf;

use Solventt\Csrf\Interfaces\CsrfTokenInterface;
use Solventt\Csrf\Interfaces\SecurityInterface;
use Solventt\Csrf\Interfaces\TokenStorageInterface;

class MaskedCsrfToken implements CsrfTokenInterface
{
    public function __construct(
        private TokenStorageInterface $storage,
        private SecurityInterface $security,
        private string $name = CsrfTokenInterface::DEFAULT_NAME
    ) {}

    /**
     * @inheritDoc
     */
    public function getValue(bool $masked = true, int $length = 32): string
    {
        if (empty($token = $this->storage->get($this->name))) {
            $token = $this->security->generateToken($length);
            $this->storage->set($this->name, $token);
        }

        return $masked ? $this->security->addMask($token) : $token;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function equals(string $requestToken): bool
    {
        return hash_equals($this->getValue(false), $this->security->removeMask($requestToken));
    }

    /**
     * Regenerates the CSRF token and puts it into a storage
     *
     * @param int $length a token length
     */
    public function regenerate(int $length = 32): void
    {
        $this->storage->remove($this->name);
        $token = $this->security->generateToken($length);
        $this->storage->set($this->name, $token);
    }
}