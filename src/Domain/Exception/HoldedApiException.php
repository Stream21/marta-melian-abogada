<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class HoldedApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function fromResponse(int $statusCode, string $body): self
    {
        return new self(
            message: sprintf('Holded API error %d: %s', $statusCode, $body),
            statusCode: $statusCode,
        );
    }
}
