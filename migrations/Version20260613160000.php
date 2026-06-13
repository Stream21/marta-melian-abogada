<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Trazabilidad de firmas: IP del firmante en expediente_firma_documento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_firma_documento ADD COLUMN IF NOT EXISTS cliente_ip VARCHAR(45) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_firma_documento DROP COLUMN IF EXISTS cliente_ip');
    }
}
