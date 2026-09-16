<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Parsea la fecha límite de la siguiente fase al avanzar.
 * Obligatoria: la abogada debe indicar cuánto tiempo de espera otorga.
 */
final class FechaVencimientoFaseParser
{
    public function parseRequired(?string $fecha): \DateTimeImmutable
    {
        if (null === $fecha || '' === trim($fecha)) {
            throw new \InvalidArgumentException(
                'Debe indicar la fecha límite (duración de espera) de la siguiente fase.',
            );
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($fecha));
        if (false === $parsed) {
            throw new \InvalidArgumentException('Fecha de vencimiento no válida.');
        }

        $fechaLimite = $parsed->setTime(23, 59, 59);
        $hoy = (new \DateTimeImmutable('today'))->setTime(0, 0, 0);
        if ($fechaLimite < $hoy) {
            throw new \InvalidArgumentException('La fecha límite no puede ser anterior a hoy.');
        }

        return $fechaLimite;
    }
}
