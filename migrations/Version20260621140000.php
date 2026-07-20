<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Responsable actual por documento entregado en requerimientos (handoff abogado/cliente)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE expediente_documento_entregado ADD responsable_actual VARCHAR(20) NOT NULL DEFAULT 'cliente'");

        $this->addSql(<<<'SQL'
            UPDATE expediente_documento_entregado
            SET responsable_actual = 'abogado'
            WHERE estado = 'entregado' AND subido_por = 'cliente'
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE expediente_documento_entregado
            SET responsable_actual = 'cliente'
            WHERE estado IN ('pendiente', 'rechazado')
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE expediente_documento_entregado
            SET responsable_actual = subido_por
            WHERE estado = 'validado'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_documento_entregado DROP responsable_actual');
    }
}
