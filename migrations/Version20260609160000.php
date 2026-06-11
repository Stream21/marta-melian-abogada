<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change tramite_documento_requerido.fase to integer 1-4';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite_documento_requerido ADD fase_int INT NOT NULL DEFAULT 2');
        $this->addSql("UPDATE tramite_documento_requerido SET fase_int = 2 WHERE fase = 'apertura'");
        $this->addSql("UPDATE tramite_documento_requerido SET fase_int = 4 WHERE fase = 'resolucion'");
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP COLUMN fase');
        $this->addSql('ALTER TABLE tramite_documento_requerido RENAME COLUMN fase_int TO fase');
        $this->addSql('ALTER TABLE tramite_documento_requerido ADD CONSTRAINT chk_tramite_doc_req_fase CHECK (fase >= 1 AND fase <= 4)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP CONSTRAINT chk_tramite_doc_req_fase');
        $this->addSql('ALTER TABLE tramite_documento_requerido ADD fase_str VARCHAR(20) NOT NULL DEFAULT \'apertura\'');
        $this->addSql("UPDATE tramite_documento_requerido SET fase_str = 'apertura' WHERE fase = 2");
        $this->addSql("UPDATE tramite_documento_requerido SET fase_str = 'resolucion' WHERE fase = 4");
        $this->addSql("UPDATE tramite_documento_requerido SET fase_str = 'apertura' WHERE fase IN (1, 3)");
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP COLUMN fase');
        $this->addSql('ALTER TABLE tramite_documento_requerido RENAME COLUMN fase_str TO fase');
    }
}
