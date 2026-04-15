<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415140209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE module_project (module_id INT NOT NULL, project_id INT NOT NULL, PRIMARY KEY (module_id, project_id))');
        $this->addSql('CREATE INDEX IDX_84C8EEF7AFC2B591 ON module_project (module_id)');
        $this->addSql('CREATE INDEX IDX_84C8EEF7166D1F9C ON module_project (project_id)');
        $this->addSql('CREATE TABLE module_skill (module_id INT NOT NULL, skill_id INT NOT NULL, PRIMARY KEY (module_id, skill_id))');
        $this->addSql('CREATE INDEX IDX_57319419AFC2B591 ON module_skill (module_id)');
        $this->addSql('CREATE INDEX IDX_573194195585C142 ON module_skill (skill_id)');
        $this->addSql('CREATE TABLE module_evaluation (module_id INT NOT NULL, evaluation_id INT NOT NULL, PRIMARY KEY (module_id, evaluation_id))');
        $this->addSql('CREATE INDEX IDX_86C68EE8AFC2B591 ON module_evaluation (module_id)');
        $this->addSql('CREATE INDEX IDX_86C68EE8456C5646 ON module_evaluation (evaluation_id)');
        $this->addSql('CREATE TABLE project_skill (project_id INT NOT NULL, skill_id INT NOT NULL, PRIMARY KEY (project_id, skill_id))');
        $this->addSql('CREATE INDEX IDX_4D68EDE9166D1F9C ON project_skill (project_id)');
        $this->addSql('CREATE INDEX IDX_4D68EDE95585C142 ON project_skill (skill_id)');
        $this->addSql('CREATE TABLE project_evaluation (project_id INT NOT NULL, evaluation_id INT NOT NULL, PRIMARY KEY (project_id, evaluation_id))');
        $this->addSql('CREATE INDEX IDX_B85DE47166D1F9C ON project_evaluation (project_id)');
        $this->addSql('CREATE INDEX IDX_B85DE47456C5646 ON project_evaluation (evaluation_id)');
        $this->addSql('CREATE TABLE skill_evaluation (skill_id INT NOT NULL, evaluation_id INT NOT NULL, PRIMARY KEY (skill_id, evaluation_id))');
        $this->addSql('CREATE INDEX IDX_6AA79075585C142 ON skill_evaluation (skill_id)');
        $this->addSql('CREATE INDEX IDX_6AA7907456C5646 ON skill_evaluation (evaluation_id)');
        $this->addSql('ALTER TABLE module_project ADD CONSTRAINT FK_84C8EEF7AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_project ADD CONSTRAINT FK_84C8EEF7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_skill ADD CONSTRAINT FK_57319419AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_skill ADD CONSTRAINT FK_573194195585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_evaluation ADD CONSTRAINT FK_86C68EE8AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_evaluation ADD CONSTRAINT FK_86C68EE8456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_skill ADD CONSTRAINT FK_4D68EDE9166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_skill ADD CONSTRAINT FK_4D68EDE95585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_evaluation ADD CONSTRAINT FK_B85DE47166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_evaluation ADD CONSTRAINT FK_B85DE47456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_evaluation ADD CONSTRAINT FK_6AA79075585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_evaluation ADD CONSTRAINT FK_6AA7907456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module_project DROP CONSTRAINT FK_84C8EEF7AFC2B591');
        $this->addSql('ALTER TABLE module_project DROP CONSTRAINT FK_84C8EEF7166D1F9C');
        $this->addSql('ALTER TABLE module_skill DROP CONSTRAINT FK_57319419AFC2B591');
        $this->addSql('ALTER TABLE module_skill DROP CONSTRAINT FK_573194195585C142');
        $this->addSql('ALTER TABLE module_evaluation DROP CONSTRAINT FK_86C68EE8AFC2B591');
        $this->addSql('ALTER TABLE module_evaluation DROP CONSTRAINT FK_86C68EE8456C5646');
        $this->addSql('ALTER TABLE project_skill DROP CONSTRAINT FK_4D68EDE9166D1F9C');
        $this->addSql('ALTER TABLE project_skill DROP CONSTRAINT FK_4D68EDE95585C142');
        $this->addSql('ALTER TABLE project_evaluation DROP CONSTRAINT FK_B85DE47166D1F9C');
        $this->addSql('ALTER TABLE project_evaluation DROP CONSTRAINT FK_B85DE47456C5646');
        $this->addSql('ALTER TABLE skill_evaluation DROP CONSTRAINT FK_6AA79075585C142');
        $this->addSql('ALTER TABLE skill_evaluation DROP CONSTRAINT FK_6AA7907456C5646');
        $this->addSql('DROP TABLE module_project');
        $this->addSql('DROP TABLE module_skill');
        $this->addSql('DROP TABLE module_evaluation');
        $this->addSql('DROP TABLE project_skill');
        $this->addSql('DROP TABLE project_evaluation');
        $this->addSql('DROP TABLE skill_evaluation');
    }
}
