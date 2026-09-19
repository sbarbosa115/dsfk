<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918231430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, stored_name VARCHAR(100) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, created_at DATETIME NOT NULL, project_id INT NOT NULL, movement_id INT DEFAULT NULL, uploaded_by_id INT NOT NULL, UNIQUE INDEX UNIQ_795FD9BB1185AF6A (stored_name), INDEX IDX_795FD9BB166D1F9C (project_id), INDEX IDX_795FD9BB229E70A7 (movement_id), INDEX IDX_795FD9BBA2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fund_movement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, date DATE NOT NULL, amount BIGINT NOT NULL, method VARCHAR(20) DEFAULT NULL, reference VARCHAR(100) DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, voided_at DATETIME DEFAULT NULL, void_reason LONGTEXT DEFAULT NULL, project_id INT NOT NULL, created_by_id INT NOT NULL, voided_by_id INT DEFAULT NULL, INDEX idx_movement_project_date (project_id, date), INDEX IDX_FB3168E8166D1F9C (project_id), INDEX IDX_FB3168E8B03A8386 (created_by_id), INDEX IDX_FB3168E8291CD485 (voided_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ledger_entry (id INT AUTO_INCREMENT NOT NULL, account VARCHAR(20) NOT NULL, amount BIGINT NOT NULL, movement_id INT NOT NULL, project_id INT NOT NULL, stage_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX idx_entry_account (project_id, account, stage_id), INDEX IDX_64272A69229E70A7 (movement_id), INDEX IDX_64272A69166D1F9C (project_id), INDEX IDX_64272A692298D193 (stage_id), INDEX IDX_64272A6912469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB229E70A7 FOREIGN KEY (movement_id) REFERENCES fund_movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE fund_movement ADD CONSTRAINT FK_FB3168E8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fund_movement ADD CONSTRAINT FK_FB3168E8B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE fund_movement ADD CONSTRAINT FK_FB3168E8291CD485 FOREIGN KEY (voided_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69229E70A7 FOREIGN KEY (movement_id) REFERENCES fund_movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A692298D193 FOREIGN KEY (stage_id) REFERENCES stage (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A6912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB166D1F9C');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB229E70A7');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBA2B28FE8');
        $this->addSql('ALTER TABLE fund_movement DROP FOREIGN KEY FK_FB3168E8166D1F9C');
        $this->addSql('ALTER TABLE fund_movement DROP FOREIGN KEY FK_FB3168E8B03A8386');
        $this->addSql('ALTER TABLE fund_movement DROP FOREIGN KEY FK_FB3168E8291CD485');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69229E70A7');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69166D1F9C');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A692298D193');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A6912469DE2');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE fund_movement');
        $this->addSql('DROP TABLE ledger_entry');
    }
}
