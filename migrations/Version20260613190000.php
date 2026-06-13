<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fecha firma contrato y calendario de cuotas en expediente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS fecha_firma_contrato TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS calendario_pagos JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS calendario_pagos');
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS fecha_firma_contrato');
    }
}
