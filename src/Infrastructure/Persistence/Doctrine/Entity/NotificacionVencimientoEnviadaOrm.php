<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notificacion_vencimiento_enviada')]
#[ORM\UniqueConstraint(
    name: 'uniq_notif_venc_exp_fecha_dia',
    columns: ['expediente_id', 'fecha_vencimiento', 'dia_relativo'],
)]
class NotificacionVencimientoEnviadaOrm
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: 'string', length: 36)]
    private string $expedienteId;

    #[ORM\Column(name: 'fecha_vencimiento', type: 'date_immutable')]
    private \DateTimeImmutable $fechaVencimiento;

    #[ORM\Column(name: 'dia_relativo', type: 'smallint')]
    private int $diaRelativo;

    #[ORM\Column(name: 'enviado_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $enviadoAt;

    public function __construct(
        string $id,
        string $expedienteId,
        \DateTimeImmutable $fechaVencimiento,
        int $diaRelativo,
        \DateTimeImmutable $enviadoAt,
    ) {
        $this->id = $id;
        $this->expedienteId = $expedienteId;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->diaRelativo = $diaRelativo;
        $this->enviadoAt = $enviadoAt;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
