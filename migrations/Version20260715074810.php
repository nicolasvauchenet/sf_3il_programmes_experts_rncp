<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715074810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(255) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, is_accepted BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, logged_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON app_user (email)');
        $this->addSql('CREATE TABLE block (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, position INTEGER NOT NULL, framework_id INTEGER NOT NULL, CONSTRAINT FK_831B972237AECF72 FOREIGN KEY (framework_id) REFERENCES framework (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_831B972237AECF72 ON block (framework_id)');
        $this->addSql('CREATE TABLE chapter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, items CLOB DEFAULT NULL, position INTEGER DEFAULT NULL, module_id INTEGER DEFAULT NULL, project_id INTEGER DEFAULT NULL, CONSTRAINT FK_F981B52EAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F981B52E166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F981B52EAFC2B591 ON chapter (module_id)');
        $this->addSql('CREATE INDEX IDX_F981B52E166D1F9C ON chapter (project_id)');
        $this->addSql('CREATE TABLE criteria (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, position INTEGER NOT NULL, skill_id INTEGER NOT NULL, CONSTRAINT FK_B61F9B815585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B61F9B815585C142 ON criteria (skill_id)');
        $this->addSql('CREATE TABLE evaluation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, modalities CLOB DEFAULT NULL, validation_rules CLOB DEFAULT NULL, position INTEGER NOT NULL, framework_id INTEGER NOT NULL, block_id INTEGER NOT NULL, CONSTRAINT FK_1323A57537AECF72 FOREIGN KEY (framework_id) REFERENCES framework (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1323A575E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1323A57537AECF72 ON evaluation (framework_id)');
        $this->addSql('CREATE INDEX IDX_1323A575E9ED820C ON evaluation (block_id)');
        $this->addSql('CREATE TABLE evaluation_part (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, duration INTEGER DEFAULT NULL, points INTEGER DEFAULT NULL, coefficient INTEGER DEFAULT NULL, position INTEGER NOT NULL, evaluation_id INTEGER NOT NULL, CONSTRAINT FK_4493F7E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4493F7E456C5646 ON evaluation_part (evaluation_id)');
        $this->addSql('CREATE TABLE framework (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, level INTEGER NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE module (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, duration_days INTEGER DEFAULT NULL, duration_hours INTEGER DEFAULT NULL, objectives CLOB DEFAULT NULL, prerequisites CLOB DEFAULT NULL, exercises CLOB DEFAULT NULL, bibliography CLOB DEFAULT NULL, online_resources CLOB DEFAULT NULL, teaching_methods CLOB DEFAULT NULL, position INTEGER NOT NULL, promotion_id INTEGER NOT NULL, block_id INTEGER NOT NULL, CONSTRAINT FK_C242628139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C242628E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C242628139DF194 ON module (promotion_id)');
        $this->addSql('CREATE INDEX IDX_C242628E9ED820C ON module (block_id)');
        $this->addSql('CREATE TABLE module_project (module_id INTEGER NOT NULL, project_id INTEGER NOT NULL, PRIMARY KEY (module_id, project_id), CONSTRAINT FK_84C8EEF7AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_84C8EEF7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_84C8EEF7AFC2B591 ON module_project (module_id)');
        $this->addSql('CREATE INDEX IDX_84C8EEF7166D1F9C ON module_project (project_id)');
        $this->addSql('CREATE TABLE module_skill (module_id INTEGER NOT NULL, skill_id INTEGER NOT NULL, PRIMARY KEY (module_id, skill_id), CONSTRAINT FK_57319419AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_573194195585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_57319419AFC2B591 ON module_skill (module_id)');
        $this->addSql('CREATE INDEX IDX_573194195585C142 ON module_skill (skill_id)');
        $this->addSql('CREATE TABLE module_evaluation (module_id INTEGER NOT NULL, evaluation_id INTEGER NOT NULL, PRIMARY KEY (module_id, evaluation_id), CONSTRAINT FK_86C68EE8AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_86C68EE8456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_86C68EE8AFC2B591 ON module_evaluation (module_id)');
        $this->addSql('CREATE INDEX IDX_86C68EE8456C5646 ON module_evaluation (evaluation_id)');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, duration_days INTEGER DEFAULT NULL, duration_hours INTEGER DEFAULT NULL, objectives CLOB DEFAULT NULL, prerequisites CLOB DEFAULT NULL, position INTEGER NOT NULL, promotion_id INTEGER NOT NULL, block_id INTEGER NOT NULL, CONSTRAINT FK_2FB3D0EE139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2FB3D0EEE9ED820C FOREIGN KEY (block_id) REFERENCES block (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE139DF194 ON project (promotion_id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEE9ED820C ON project (block_id)');
        $this->addSql('CREATE TABLE project_skill (project_id INTEGER NOT NULL, skill_id INTEGER NOT NULL, PRIMARY KEY (project_id, skill_id), CONSTRAINT FK_4D68EDE9166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4D68EDE95585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4D68EDE9166D1F9C ON project_skill (project_id)');
        $this->addSql('CREATE INDEX IDX_4D68EDE95585C142 ON project_skill (skill_id)');
        $this->addSql('CREATE TABLE project_evaluation (project_id INTEGER NOT NULL, evaluation_id INTEGER NOT NULL, PRIMARY KEY (project_id, evaluation_id), CONSTRAINT FK_B85DE47166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B85DE47456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B85DE47166D1F9C ON project_evaluation (project_id)');
        $this->addSql('CREATE INDEX IDX_B85DE47456C5646 ON project_evaluation (evaluation_id)');
        $this->addSql('CREATE TABLE promotion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, program VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, framework_id INTEGER NOT NULL, CONSTRAINT FK_C11D7DD137AECF72 FOREIGN KEY (framework_id) REFERENCES framework (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C11D7DD137AECF72 ON promotion (framework_id)');
        $this->addSql('CREATE TABLE skill (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, position INTEGER NOT NULL, framework_id INTEGER NOT NULL, block_id INTEGER NOT NULL, CONSTRAINT FK_5E3DE47737AECF72 FOREIGN KEY (framework_id) REFERENCES framework (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5E3DE477E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5E3DE47737AECF72 ON skill (framework_id)');
        $this->addSql('CREATE INDEX IDX_5E3DE477E9ED820C ON skill (block_id)');
        $this->addSql('CREATE TABLE skill_evaluation (skill_id INTEGER NOT NULL, evaluation_id INTEGER NOT NULL, PRIMARY KEY (skill_id, evaluation_id), CONSTRAINT FK_6AA79075585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6AA7907456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6AA79075585C142 ON skill_evaluation (skill_id)');
        $this->addSql('CREATE INDEX IDX_6AA7907456C5646 ON skill_evaluation (evaluation_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE block');
        $this->addSql('DROP TABLE chapter');
        $this->addSql('DROP TABLE criteria');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE evaluation_part');
        $this->addSql('DROP TABLE framework');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE module_project');
        $this->addSql('DROP TABLE module_skill');
        $this->addSql('DROP TABLE module_evaluation');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_skill');
        $this->addSql('DROP TABLE project_evaluation');
        $this->addSql('DROP TABLE promotion');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE skill_evaluation');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
