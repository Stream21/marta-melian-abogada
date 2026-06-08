<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608184000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at to area_juridica, servicio and tramite';
    }

    public function up(Schema $schema): void
    {
        foreach (['area_juridica', 'servicio', 'tramite'] as $table) {
            $this->addSql("ALTER TABLE {$table} ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()");
            $this->addSql("ALTER TABLE {$table} ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()");
            $this->addSql("UPDATE {$table} SET created_at = NOW(), updated_at = NOW()");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['area_juridica', 'servicio', 'tramite'] as $table) {
            $this->addSql("ALTER TABLE {$table} DROP COLUMN updated_at");
            $this->addSql("ALTER TABLE {$table} DROP COLUMN created_at");
        }
    }
}
