<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PaymentType: string
{
    case Manual = 'manual';
    case Link = 'link';
    case Installment = 'installment';
}
