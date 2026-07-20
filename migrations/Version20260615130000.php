<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Origen de subida en documentos entregados de requerimientos (cliente o abogado)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE expediente_documento_entregado ADD COLUMN IF NOT EXISTS subido_por VARCHAR(20) NOT NULL DEFAULT 'cliente'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_documento_entregado DROP COLUMN IF EXISTS subido_por');
    }
}
