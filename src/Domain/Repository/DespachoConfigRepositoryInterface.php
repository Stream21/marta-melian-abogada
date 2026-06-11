<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\DespachoConfig;

interface DespachoConfigRepositoryInterface
{
    public function find(): ?DespachoConfig;

    public function save(DespachoConfig $config): void;
}
