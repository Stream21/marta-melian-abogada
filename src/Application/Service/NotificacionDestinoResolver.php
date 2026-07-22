<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ActorHitoExpediente;

final class NotificacionDestinoResolver
{
    public function esNotificable(string $tipo, ActorHitoExpediente $actor): bool
    {
        if (ActorHitoExpediente::Cliente === $actor) {
            return true;
        }

        return 'holded_sync_fallido' === $tipo;
    }

    /**
     * @return array{tab: string, hitoId: string, paso: ?string, referenciaId: ?string, abrirRevision: bool}
     */
    public function resolve(string $tipo, ?string $paso, ?string $referenciaId, string $hitoId): array
    {
        $abrirRevision = in_array($tipo, [
            'paso_completado',
            'documento_subido',
            'documento_firmado',
            'documento_requerimientos_subido',
            'documento_requerimientos_devuelto',
        ], true);

        if ('holded_sync_fallido' === $tipo) {
            return [
                'tab' => 'facturacion',
                'hitoId' => $hitoId,
                'paso' => null,
                'referenciaId' => $referenciaId,
                'abrirRevision' => false,
            ];
        }

        if (str_starts_with($tipo, 'documento_requerimientos_')) {
            return [
                'tab' => 'gestion',
                'hitoId' => $hitoId,
                'paso' => null,
                'referenciaId' => $referenciaId,
                'abrirRevision' => $abrirRevision,
            ];
        }

        if (null !== $paso && '' !== $paso) {
            return [
                'tab' => 'gestion',
                'hitoId' => $hitoId,
                'paso' => $paso,
                'referenciaId' => $referenciaId,
                'abrirRevision' => $abrirRevision,
            ];
        }

        return [
            'tab' => 'gestion',
            'hitoId' => $hitoId,
            'paso' => null,
            'referenciaId' => $referenciaId,
            'abrirRevision' => false,
        ];
    }
}
