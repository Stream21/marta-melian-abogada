<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recordatorio futuro: servicio y trámite programados.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro ADD COLUMN IF NOT EXISTS servicio_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro ADD COLUMN IF NOT EXISTS tramite_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro ADD COLUMN IF NOT EXISTS servicio_nombre VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro ADD COLUMN IF NOT EXISTS tramite_nombre VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro DROP COLUMN IF EXISTS servicio_id');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro DROP COLUMN IF EXISTS tramite_id');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro DROP COLUMN IF EXISTS servicio_nombre');
        $this->addSql('ALTER TABLE expediente_recordatorio_futuro DROP COLUMN IF EXISTS tramite_nombre');
    }
}
