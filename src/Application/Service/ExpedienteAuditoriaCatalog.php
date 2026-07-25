<?php

declare(strict_types=1);

namespace App\Application\Service;

final class ExpedienteAuditoriaCatalog
{
    public function categoriaForTipo(string $tipo): string
    {
        if (str_starts_with($tipo, 'documento_requerimientos') || 'documento_requerido_anadido' === $tipo || 'fase_requerimientos_iniciada' === $tipo) {
            return 'requerimientos';
        }

        if (str_contains($tipo, 'presentacion_telematica') || str_contains($tipo, 'seguimiento_extranjeria') || str_contains($tipo, 'requerimiento_mercurio') || 'fase_tramitacion_iniciada' === $tipo || 'fase_resolucion_iniciada' === $tipo) {
            return 'tramitacion';
        }

        if (
            str_contains($tipo, 'resolucion')
            || str_contains($tipo, 'recordatorio_futuro')
            || 'expediente_archivado' === $tipo
        ) {
            return 'resolucion';
        }

        if (str_starts_with($tipo, 'paso_') || in_array($tipo, [
            'contratacion_iniciada',
            'condiciones_pago_actualizadas',
        ], true)) {
            return 'contratacion';
        }

        if (str_contains($tipo, 'documento') || 'documento_firmado' === $tipo) {
            return 'documento';
        }

        if (str_contains($tipo, 'pago') || 'pago_registrado' === $tipo || 'holded_sync_fallido' === $tipo) {
            return 'pago';
        }

        if (
            str_contains($tipo, 'notif')
            || str_contains($tipo, 'whatsapp')
            || str_contains($tipo, 'email')
            || str_contains($tipo, 'sms')
            || str_contains($tipo, 'otp')
        ) {
            return 'comunicacion';
        }

        if ('fase_completada' === $tipo || str_contains($tipo, 'fase_') || str_starts_with($tipo, 'expediente_')) {
            return 'estado';
        }

        return 'sistema';
    }

    public function categoriaLabel(string $categoria): string
    {
        return match ($categoria) {
            'contratacion' => 'Contratación',
            'requerimientos' => 'Requerimientos',
            'tramitacion' => 'Tramitación',
            'resolucion' => 'Resolución',
            'comunicacion' => 'Comunicación',
            'pago' => 'Pago',
            'documento' => 'Documento',
            'estado' => 'Estado',
            default => 'Sistema',
        };
    }

    public function actorLabel(string $actor): string
    {
        return match ($actor) {
            'cliente' => 'Cliente',
            'abogado' => 'Abogado',
            default => 'Sistema',
        };
    }

    public function tipoLabel(string $tipo): string
    {
        return match ($tipo) {
            'contratacion_iniciada' => 'Inicio contratación',
            'paso_completado' => 'Paso completado',
            'paso_validado' => 'Paso validado',
            'paso_devuelto' => 'Devuelto al cliente',
            'documento_firmado' => 'Documento firmado',
            'documento_subido' => 'Documento subido',
            'condiciones_pago_actualizadas' => 'Condiciones de pago',
            'fase_completada' => 'Cambio de fase',
            'pago_registrado' => 'Cobro registrado',
            'pago_stripe_completado' => 'Pago Stripe',
            'holded_sync_fallido' => 'Fallo sincronización Holded',
            'otp_firma_verificado' => 'OTP verificado',
            'notificacion_enviada' => 'Notificación enviada',
            'notificacion_alta_expediente' => 'Alta expediente notificada',
            'notificacion_enlace_enviado' => 'Enlace enviado al cliente',
            'fase_requerimientos_iniciada' => 'Inicio requerimientos',
            'fase_tramitacion_iniciada' => 'Inicio tramitación',
            'fase_resolucion_iniciada' => 'Inicio resolución',
            'resolucion_registrada' => 'Resolución registrada',
            'resolucion_gestiones_actualizadas' => 'Gestiones post-resolución',
            'recordatorio_futuro_programado' => 'Recordatorio futuro programado',
            'expediente_archivado' => 'Expediente archivado',
            'presentacion_telematica_registrada' => 'Presentación telemática',
            'seguimiento_extranjeria_asignado' => 'Seguimiento extranjería',
            'requerimiento_mercurio_anadido' => 'Requerimiento Mercurio añadido',
            'requerimiento_mercurio_archivo' => 'Archivo requerimiento Mercurio',
            'requerimiento_mercurio_presentado' => 'Requerimiento Mercurio presentado',
            'documento_requerimientos_subido' => 'Documento subido (requerimientos)',
            'documento_requerimientos_validado' => 'Documento validado (requerimientos)',
            'documento_requerimientos_devuelto' => 'Documento devuelto (requerimientos)',
            'documento_requerido_anadido' => 'Documento requerido añadido',
            'expediente_cancelado' => 'Expediente cancelado',
            'expediente_reabierto' => 'Expediente reabierto',
            'expediente_archivado' => 'Expediente archivado',
            'expediente_finalizado' => 'Expediente archivado',
            default => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }

    public function canalForTipo(string $tipo, ?string $descripcion = null): ?string
    {
        if (str_contains($tipo, 'whatsapp') || (null !== $descripcion && str_contains(strtolower($descripcion), 'whatsapp'))) {
            return 'whatsapp';
        }

        if (str_contains($tipo, 'email') || (null !== $descripcion && str_contains(strtolower($descripcion), 'email'))) {
            return 'email';
        }

        if (str_contains($tipo, 'sms') || str_contains($tipo, 'otp') || (null !== $descripcion && str_contains(strtolower($descripcion), 'sms'))) {
            return 'sms';
        }

        return null;
    }

    public function canalLabel(?string $canal): ?string
    {
        return match ($canal) {
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'sms' => 'SMS',
            default => null,
        };
    }
}
