<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla messenger_messages (Symfony Messenger Doctrine transport) e idempotencia de vencimientos.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS messenger_messages (
                id BIGSERIAL NOT NULL,
                body TEXT NOT NULL,
                headers TEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS notificacion_vencimiento_enviada (
                id VARCHAR(32) NOT NULL,
                expediente_id VARCHAR(36) NOT NULL,
                fecha_vencimiento DATE NOT NULL,
                dia_relativo SMALLINT NOT NULL,
                enviado_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_notif_venc_exp_fecha_dia '
            . 'ON notificacion_vencimiento_enviada (expediente_id, fecha_vencimiento, dia_relativo)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notificacion_vencimiento_enviada');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
    }
}
