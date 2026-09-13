<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\TipoCampoFormulario;

final class RequerimientoMercurioCampoOpcionesNormalizer
{
    /**
     * @param mixed $opciones
     *
     * @return list<string>|null
     */
    public static function normalizar(mixed $opciones, TipoCampoFormulario $tipo): ?array
    {
        if (!is_array($opciones)) {
            $opciones = null;
        }
        /** @var list<string>|null $opcionesNormalizadas */
        $opcionesNormalizadas = null;
        if (null !== $opciones) {
            $opcionesNormalizadas = [];
            foreach ($opciones as $opt) {
                $o = trim((string) $opt);
                if ('' !== $o) {
                    $opcionesNormalizadas[] = $o;
                }
            }
        }
        if (TipoCampoFormulario::Select === $tipo && (null === $opcionesNormalizadas || [] === $opcionesNormalizadas)) {
            throw new \InvalidArgumentException('Los campos de tipo selección necesitan al menos una opción.');
        }

        return $opcionesNormalizadas;
    }
}
