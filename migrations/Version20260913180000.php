<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renombra fase 2: requerimientos → documentacion (negocio + estados).
 */
final class Version20260913180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra fase_negocio requerimientos→documentacion y estados de fase asociados';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET fase_negocio = 'documentacion' WHERE fase_negocio = 'requerimientos'");
        $this->addSql("UPDATE expediente SET estado_fase = 'documentacion_en_progreso' WHERE estado_fase = 'requerimientos_en_progreso'");
        $this->addSql("UPDATE expediente SET estado_fase = 'documentacion_listo' WHERE estado_fase = 'requerimientos_listo'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET fase_negocio = 'requerimientos' WHERE fase_negocio = 'documentacion'");
        $this->addSql("UPDATE expediente SET estado_fase = 'requerimientos_en_progreso' WHERE estado_fase = 'documentacion_en_progreso'");
        $this->addSql("UPDATE expediente SET estado_fase = 'requerimientos_listo' WHERE estado_fase = 'documentacion_listo'");
    }
}
