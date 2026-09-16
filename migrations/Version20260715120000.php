<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store non-numeric evaluation part duration labels.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evaluation_part ADD COLUMN duration_label VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evaluation_part DROP COLUMN duration_label');
    }
}
