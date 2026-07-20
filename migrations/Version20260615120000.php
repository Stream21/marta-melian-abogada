<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Motivos estructurados de devolución en pasos de contratación';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_contratacion_paso ADD COLUMN IF NOT EXISTS motivos_devolucion JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_contratacion_paso DROP COLUMN IF EXISTS motivos_devolucion');
    }
}
