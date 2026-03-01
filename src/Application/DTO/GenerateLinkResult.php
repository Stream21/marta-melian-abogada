<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class GenerateLinkResult
{
    public function __construct(
        public bool $success,
        public ?string $url = null,
        public ?string $sessionId = null,
        public ?string $error = null,
    ) {
    }

    public static function success(string $url, string $sessionId): self
    {
        return new self(true, $url, $sessionId);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, null, $error);
    }
}
