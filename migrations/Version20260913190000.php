<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Consolida subfases de tramitación a 3 valores de negocio.
 */
final class Version20260913190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Subfases tramitación: pendiente_tramitacion | tramitado | pendiente_requerimiento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'pendiente_tramitacion' WHERE subfase_tramitacion = 'preparacion_presentacion'");
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'tramitado' WHERE subfase_tramitacion IN ('pendiente_recepcion', 'en_seguimiento', 'listo_resolucion')");
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'pendiente_requerimiento' WHERE subfase_tramitacion IN ('requerimiento_abierto', 'recopilacion_datos')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'preparacion_presentacion' WHERE subfase_tramitacion = 'pendiente_tramitacion'");
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'en_seguimiento' WHERE subfase_tramitacion = 'tramitado'");
        $this->addSql("UPDATE expediente SET subfase_tramitacion = 'requerimiento_abierto' WHERE subfase_tramitacion = 'pendiente_requerimiento'");
    }
}
