<?php

declare(strict_types=1);

namespace App\Application\Service;

final class TelefonoNormalizer
{
    public function normalize(?string $telefono): ?string
    {
        if (null === $telefono) {
            return null;
        }

        $trimmed = trim($telefono);
        if ('' === $trimmed) {
            return null;
        }

        $collapsed = preg_replace('/\s+/', '', $trimmed) ?? $trimmed;

        return $collapsed;
    }

    public function isValid(?string $telefono): bool
    {
        $normalized = $this->normalize($telefono);

        return null !== $normalized && 1 === preg_match('/^\+?[0-9]{6,20}$/', $normalized);
    }
}
