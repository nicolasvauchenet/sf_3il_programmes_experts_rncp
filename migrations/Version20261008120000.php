<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Attach downloadable documents to imported promotions.'; }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('promotion_document');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('promotion_id', 'integer');
        foreach (['label', 'filename', 'original_name'] as $name) {
            $table->addColumn($name, 'string', ['length' => 255]);
        }
        $table->addColumn('position', 'integer');
        $table->addColumn('visible', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['promotion_id']);
        $table->addForeignKeyConstraint('promotion', ['promotion_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void { $schema->dropTable('promotion_document'); }
}
