<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notificaciones: referencia_id en expediente_hito y tabla notificacion_hito_leida';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_hito ADD COLUMN IF NOT EXISTS referencia_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE TABLE IF NOT EXISTS notificacion_hito_leida (
            hito_id VARCHAR(36) NOT NULL,
            leida_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(hito_id)
        )');
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'fk_notif_hito_leida'
                ) THEN
                    ALTER TABLE notificacion_hito_leida
                        ADD CONSTRAINT FK_NOTIF_HITO_LEIDA
                        FOREIGN KEY (hito_id)
                        REFERENCES expediente_hito (id)
                        ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
                END IF;
            END $$
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notificacion_hito_leida DROP CONSTRAINT IF EXISTS FK_NOTIF_HITO_LEIDA');
        $this->addSql('DROP TABLE IF EXISTS notificacion_hito_leida');
        $this->addSql('ALTER TABLE expediente_hito DROP COLUMN IF EXISTS referencia_id');
    }
}
