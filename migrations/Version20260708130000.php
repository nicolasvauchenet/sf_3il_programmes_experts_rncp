<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activation flag to application users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD is_active BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE app_user ALTER is_active DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP is_active');
    }
}
