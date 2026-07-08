<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account acceptance flag to application users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD is_accepted BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE app_user ALTER is_accepted DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP is_accepted');
    }
}
