<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class User
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly array $roles,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }
}
