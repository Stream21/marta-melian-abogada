<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Elimina icon_key de tipo_caso (sin icono asignable)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tipo_caso DROP COLUMN IF EXISTS icon_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tipo_caso ADD icon_key VARCHAR(32) DEFAULT 'civil' NOT NULL");
    }
}
