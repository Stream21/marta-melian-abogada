<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OTP SMS en firmas: flag por trámite y sesiones OTP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite ADD COLUMN IF NOT EXISTS requiere_otp_firma BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('UPDATE tramite SET requiere_otp_firma = true WHERE requiere_otp_firma IS NULL');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_firma_otp (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            codigo_hash VARCHAR(255) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            intentos_fallidos INT DEFAULT 0 NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_firma_otp_expediente ON expediente_firma_otp (expediente_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_firma_otp_exp_verified ON expediente_firma_otp (expediente_id, verified_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS expediente_firma_otp');
        $this->addSql('ALTER TABLE tramite DROP COLUMN IF EXISTS requiere_otp_firma');
    }
}
