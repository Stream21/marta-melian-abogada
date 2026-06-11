<?php

declare(strict_types=1);

namespace App\Application\Service;

final class EmailValidator
{
    public function isValid(?string $email): bool
    {
        if (null === $email) {
            return true;
        }

        $trimmed = trim($email);
        if ('' === $trimmed) {
            return true;
        }

        return false !== filter_var($trimmed, \FILTER_VALIDATE_EMAIL);
    }

    public function assertValid(?string $email): void
    {
        if (!$this->isValid($email)) {
            throw new \InvalidArgumentException('El email no tiene un formato válido.');
        }
    }
}
