<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace tramite.descripcion with honorarios (lawyer fee)';
    }

    public function up(Schema $schema): void
    {
        // Idempotente: Version20260608150000 ya aplica el mismo cambio en algunos entornos.
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tramite' AND column_name = 'honorarios'
    ) THEN
        ALTER TABLE tramite ADD honorarios DOUBLE PRECISION NOT NULL DEFAULT 0;
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'tramite' AND column_name = 'descripcion'
    ) THEN
        ALTER TABLE tramite DROP COLUMN descripcion;
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tramite ADD descripcion TEXT NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE tramite DROP COLUMN honorarios');
    }
}
