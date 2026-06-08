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
        $this->addSql('ALTER TABLE tramite ADD honorarios DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE tramite DROP COLUMN descripcion');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tramite ADD descripcion TEXT NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE tramite DROP COLUMN honorarios');
    }
}
