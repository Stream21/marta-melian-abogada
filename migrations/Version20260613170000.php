<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hash SHA-256 del PDF firmado para verificación de integridad';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_firma_documento ADD COLUMN IF NOT EXISTS pdf_firmado_sha256 VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_firma_documento DROP COLUMN IF EXISTS pdf_firmado_sha256');
    }
}
