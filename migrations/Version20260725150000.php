<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fase 4 resolución: resolución administrativa, gestiones y recordatorio futuro.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_resolucion (
                id VARCHAR(36) NOT NULL,
                expediente_id VARCHAR(36) NOT NULL,
                outcome VARCHAR(20) NOT NULL,
                resolucion_path VARCHAR(500) NOT NULL,
                fecha_notificacion TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                gestiones_json TEXT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_resolucion_expediente ON expediente_resolucion (expediente_id)');
        $this->addSql("COMMENT ON COLUMN expediente_resolucion.fecha_notificacion IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_resolucion.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_resolucion.updated_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_recordatorio_futuro (
                id VARCHAR(36) NOT NULL,
                expediente_id VARCHAR(36) NOT NULL,
                fecha TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                motivo TEXT NOT NULL,
                notificado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_recordatorio_expediente ON expediente_recordatorio_futuro (expediente_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_recordatorio_pendiente ON expediente_recordatorio_futuro (fecha, notificado_at)');
        $this->addSql("COMMENT ON COLUMN expediente_recordatorio_futuro.fecha IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_recordatorio_futuro.notificado_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_recordatorio_futuro.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS expediente_recordatorio_futuro');
        $this->addSql('DROP TABLE IF EXISTS expediente_resolucion');
    }
}
