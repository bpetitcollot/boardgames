<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250218195923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add game creator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD created_by_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CB03A8386');
        $this->addSql('DROP INDEX IDX_232B318CB03A8386');
        $this->addSql('ALTER TABLE game DROP created_by_id');
    }
}
