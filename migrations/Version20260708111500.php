<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit timestamps and last login timestamp to application users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD logged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE app_user ALTER updated_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP created_at');
        $this->addSql('ALTER TABLE app_user DROP updated_at');
        $this->addSql('ALTER TABLE app_user DROP logged_at');
    }
}
