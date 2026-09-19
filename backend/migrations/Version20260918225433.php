<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918225433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stages, categories, budget, budget lines and milestones';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE budget (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, contingency BIGINT NOT NULL, approved_at DATETIME DEFAULT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_73F2F77B166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE budget_event (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, budget_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_50BF9F5136ABA6B8 (budget_id), INDEX IDX_50BF9F51A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE budget_line (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, unit VARCHAR(20) NOT NULL, quantity NUMERIC(14, 3) NOT NULL, unit_price BIGINT NOT NULL, total BIGINT NOT NULL, position INT NOT NULL, stage_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_ABD0B6A62298D193 (stage_id), INDEX IDX_ABD0B6A612469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, project_id INT NOT NULL, UNIQUE INDEX uniq_category_name (project_id, name), INDEX IDX_64C19C1166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE milestone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) NOT NULL, weight INT NOT NULL, planned_date DATE DEFAULT NULL, completed_at DATE DEFAULT NULL, completion_notes LONGTEXT DEFAULT NULL, position INT NOT NULL, stage_id INT NOT NULL, completed_by_id INT DEFAULT NULL, INDEX IDX_4FAC83822298D193 (stage_id), INDEX IDX_4FAC838285ECDE76 (completed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stage (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, position INT NOT NULL, status VARCHAR(20) NOT NULL, planned_start DATE DEFAULT NULL, planned_end DATE DEFAULT NULL, actual_start DATE DEFAULT NULL, actual_end DATE DEFAULT NULL, project_id INT NOT NULL, INDEX IDX_C27C9369166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77B166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE budget_event ADD CONSTRAINT FK_50BF9F5136ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE budget_event ADD CONSTRAINT FK_50BF9F51A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE budget_line ADD CONSTRAINT FK_ABD0B6A62298D193 FOREIGN KEY (stage_id) REFERENCES stage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE budget_line ADD CONSTRAINT FK_ABD0B6A612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milestone ADD CONSTRAINT FK_4FAC83822298D193 FOREIGN KEY (stage_id) REFERENCES stage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milestone ADD CONSTRAINT FK_4FAC838285ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C9369166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        // Every project owns exactly one budget; create it for projects that predate this migration.
        $this->addSql("INSERT INTO budget (project_id, status, contingency, approved_at) SELECT id, 'DRAFT', 0, NULL FROM project");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77B166D1F9C');
        $this->addSql('ALTER TABLE budget_event DROP FOREIGN KEY FK_50BF9F5136ABA6B8');
        $this->addSql('ALTER TABLE budget_event DROP FOREIGN KEY FK_50BF9F51A76ED395');
        $this->addSql('ALTER TABLE budget_line DROP FOREIGN KEY FK_ABD0B6A62298D193');
        $this->addSql('ALTER TABLE budget_line DROP FOREIGN KEY FK_ABD0B6A612469DE2');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1166D1F9C');
        $this->addSql('ALTER TABLE milestone DROP FOREIGN KEY FK_4FAC83822298D193');
        $this->addSql('ALTER TABLE milestone DROP FOREIGN KEY FK_4FAC838285ECDE76');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C9369166D1F9C');
        $this->addSql('DROP TABLE budget');
        $this->addSql('DROP TABLE budget_event');
        $this->addSql('DROP TABLE budget_line');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE milestone');
        $this->addSql('DROP TABLE stage');
    }
}
