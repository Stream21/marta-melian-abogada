<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Elimina numero_servicios de tipo_caso (se derivará de la relación con servicios)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tipo_caso DROP COLUMN IF EXISTS numero_servicios');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tipo_caso ADD numero_servicios INT DEFAULT 0 NOT NULL');
    }
}
