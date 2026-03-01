<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $pdfPath = null,
        public ?string $pdfUrl = null,
        public ?string $message = null,
        public ?string $error = null,
    ) {
    }

    public static function success(string $paymentId, ?string $pdfPath = null, ?string $pdfUrl = null): self
    {
        return new self(true, $paymentId, $pdfPath, $pdfUrl);
    }

    public static function failure(string $error, ?string $message = null): self
    {
        return new self(false, null, null, null, $message, $error);
    }
}
