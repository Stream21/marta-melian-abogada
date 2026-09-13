<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Requerimiento documento: max_archivos (límite de ficheros, no N entregas).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_requerimiento_documento ADD COLUMN IF NOT EXISTS max_archivos INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_requerimiento_documento DROP COLUMN IF EXISTS max_archivos');
    }
}
