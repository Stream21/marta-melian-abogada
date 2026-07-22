<?php

declare(strict_types=1);

namespace App\Domain\Exception;

interface ClienteDuplicadoExceptionInterface extends \Throwable
{
    public function clienteExistenteId(): string;

    public function clienteExistenteNombre(): string;

    /** @return 'telefono'|'documento' */
    public function campoDuplicado(): string;
}
