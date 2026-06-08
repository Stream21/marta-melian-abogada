<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add canal and requiere_procurador flags to tramite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tramite ADD canal VARCHAR(20) NOT NULL DEFAULT 'mercurio'");
        $this->addSql('ALTER TABLE tramite ADD requiere_procurador BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite DROP COLUMN requiere_procurador');
        $this->addSql('ALTER TABLE tramite DROP COLUMN canal');
    }
}
