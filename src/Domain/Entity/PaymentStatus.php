<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
