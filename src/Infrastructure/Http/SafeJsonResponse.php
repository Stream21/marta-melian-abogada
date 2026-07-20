<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class SafeJsonResponse extends JsonResponse
{
    public function __construct(mixed $data = null, int $status = 200, array $headers = [], bool $json = false)
    {
        if (!$json && null !== $data && !is_string($data)) {
            $data = Utf8Sanitizer::sanitizeMixed($data);
        }

        parent::__construct($data, $status, $headers, $json);
    }

    public static function message(string $message, int $status = 200): self
    {
        return new self(['message' => Utf8Sanitizer::sanitize($message)], $status);
    }
}
