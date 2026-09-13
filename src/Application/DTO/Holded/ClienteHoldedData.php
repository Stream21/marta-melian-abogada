<?php

declare(strict_types=1);

namespace App\Application\DTO\Holded;

/**
 * Datos de cliente para crear/reutilizar contacto en Holded (B2C, persona física).
 */
final readonly class ClienteHoldedData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $documentNumber,
        public string $documentType = '',
        public string $address = '',
        public string $city = '',
        public string $postalCode = '',
        public string $countryCode = 'ES',
        public ?string $existingHoldedContactId = null,
        public string $phone = '',
    ) {
    }
}
