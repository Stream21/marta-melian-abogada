<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alta expediente: fases, honorarios, token acceso, teléfono único en cliente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE cliente SET telefono = TRIM(telefono) WHERE telefono IS NOT NULL");
        $this->addSql("UPDATE cliente SET telefono = NULL WHERE telefono = ''");

        $this->addSql('ALTER TABLE cliente ALTER COLUMN telefono DROP DEFAULT');
        $this->addSql('ALTER TABLE cliente ALTER COLUMN telefono DROP NOT NULL');

        $this->addSql('ALTER TABLE expediente ADD fase_negocio VARCHAR(30) DEFAULT \'contratacion\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD estado_fase VARCHAR(30) DEFAULT \'pendiente_cliente\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD honorarios_acordados NUMERIC(10, 2) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD metodo_pago VARCHAR(20) DEFAULT \'manual\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD plan_pago VARCHAR(20) DEFAULT \'unico\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD num_cuotas SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD access_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente ADD servicio_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EXPEDIENTE_ACCESS_TOKEN ON expediente (access_token)');
        $this->addSql('CREATE INDEX IDX_EXPEDIENTE_SERVICIO ON expediente (servicio_id)');
        $this->addSql('ALTER TABLE expediente ADD CONSTRAINT FK_EXPEDIENTE_SERVICIO FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLIENTE_TELEFONO ON cliente (telefono) WHERE telefono IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CLIENTE_TELEFONO');

        $this->addSql('ALTER TABLE expediente DROP CONSTRAINT FK_EXPEDIENTE_SERVICIO');
        $this->addSql('DROP INDEX IDX_EXPEDIENTE_SERVICIO');
        $this->addSql('DROP INDEX UNIQ_EXPEDIENTE_ACCESS_TOKEN');
        $this->addSql('ALTER TABLE expediente DROP fase_negocio');
        $this->addSql('ALTER TABLE expediente DROP estado_fase');
        $this->addSql('ALTER TABLE expediente DROP honorarios_acordados');
        $this->addSql('ALTER TABLE expediente DROP metodo_pago');
        $this->addSql('ALTER TABLE expediente DROP plan_pago');
        $this->addSql('ALTER TABLE expediente DROP num_cuotas');
        $this->addSql('ALTER TABLE expediente DROP access_token');
        $this->addSql('ALTER TABLE expediente DROP servicio_id');

        $this->addSql('ALTER TABLE cliente ALTER COLUMN telefono SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE cliente ALTER COLUMN telefono SET NOT NULL');
        $this->addSql("UPDATE cliente SET telefono = '' WHERE telefono IS NULL");
    }
}
