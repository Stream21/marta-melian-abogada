<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tipo and max_imagenes to tramite_documento_requerido';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tramite_documento_requerido ADD tipo VARCHAR(20) NOT NULL DEFAULT 'individual'");
        $this->addSql('ALTER TABLE tramite_documento_requerido ADD max_imagenes INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP COLUMN max_imagenes');
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP COLUMN tipo');
    }
}
