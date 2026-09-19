<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918233257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, amount BIGINT NOT NULL, description VARCHAR(255) NOT NULL, supplier VARCHAR(150) DEFAULT NULL, invoice_number VARCHAR(60) DEFAULT NULL, paid_from VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, project_id INT NOT NULL, stage_id INT NOT NULL, category_id INT NOT NULL, paid_by_id INT NOT NULL, movement_id INT DEFAULT NULL, reimbursement_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_2D3A8DA6229E70A7 (movement_id), INDEX idx_expense_project_status (project_id, status), INDEX IDX_2D3A8DA6166D1F9C (project_id), INDEX IDX_2D3A8DA62298D193 (stage_id), INDEX IDX_2D3A8DA612469DE2 (category_id), INDEX IDX_2D3A8DA67F9BC654 (paid_by_id), INDEX IDX_2D3A8DA6AF12B54B (reimbursement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE expense_event (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, comment LONGTEXT DEFAULT NULL, previous JSON DEFAULT NULL, created_at DATETIME NOT NULL, expense_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_98A6AD9DF395DB7B (expense_id), INDEX IDX_98A6AD9DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE petty_cash_cycle (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, status VARCHAR(20) NOT NULL, opening_balance BIGINT NOT NULL, closing_balance BIGINT DEFAULT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, closing_note LONGTEXT DEFAULT NULL, signed_off_at DATETIME DEFAULT NULL, project_id INT NOT NULL, closed_by_id INT DEFAULT NULL, signed_off_by_id INT DEFAULT NULL, UNIQUE INDEX uniq_cycle_number (project_id, number), INDEX IDX_F97D9926166D1F9C (project_id), INDEX IDX_F97D9926E1FA7797 (closed_by_id), INDEX IDX_F97D9926C79A3DFF (signed_off_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reimbursement (id INT AUTO_INCREMENT NOT NULL, method VARCHAR(20) NOT NULL, reference VARCHAR(100) DEFAULT NULL, project_id INT NOT NULL, movement_id INT NOT NULL, UNIQUE INDEX UNIQ_705D16B7229E70A7 (movement_id), INDEX IDX_705D16B7166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA62298D193 FOREIGN KEY (stage_id) REFERENCES stage (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA67F9BC654 FOREIGN KEY (paid_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6229E70A7 FOREIGN KEY (movement_id) REFERENCES fund_movement (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6AF12B54B FOREIGN KEY (reimbursement_id) REFERENCES reimbursement (id)');
        $this->addSql('ALTER TABLE expense_event ADD CONSTRAINT FK_98A6AD9DF395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expense_event ADD CONSTRAINT FK_98A6AD9DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE petty_cash_cycle ADD CONSTRAINT FK_F97D9926166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE petty_cash_cycle ADD CONSTRAINT FK_F97D9926E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE petty_cash_cycle ADD CONSTRAINT FK_F97D9926C79A3DFF FOREIGN KEY (signed_off_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reimbursement ADD CONSTRAINT FK_705D16B7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reimbursement ADD CONSTRAINT FK_705D16B7229E70A7 FOREIGN KEY (movement_id) REFERENCES fund_movement (id)');
        $this->addSql('ALTER TABLE attachment ADD expense_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBF395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_795FD9BBF395DB7B ON attachment (expense_id)');
        $this->addSql('ALTER TABLE fund_movement ADD petty_cash_cycle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fund_movement ADD CONSTRAINT FK_FB3168E8D4B6C349 FOREIGN KEY (petty_cash_cycle_id) REFERENCES petty_cash_cycle (id)');
        $this->addSql('CREATE INDEX IDX_FB3168E8D4B6C349 ON fund_movement (petty_cash_cycle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6166D1F9C');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA62298D193');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA612469DE2');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA67F9BC654');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6229E70A7');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6AF12B54B');
        $this->addSql('ALTER TABLE expense_event DROP FOREIGN KEY FK_98A6AD9DF395DB7B');
        $this->addSql('ALTER TABLE expense_event DROP FOREIGN KEY FK_98A6AD9DA76ED395');
        $this->addSql('ALTER TABLE petty_cash_cycle DROP FOREIGN KEY FK_F97D9926166D1F9C');
        $this->addSql('ALTER TABLE petty_cash_cycle DROP FOREIGN KEY FK_F97D9926E1FA7797');
        $this->addSql('ALTER TABLE petty_cash_cycle DROP FOREIGN KEY FK_F97D9926C79A3DFF');
        $this->addSql('ALTER TABLE reimbursement DROP FOREIGN KEY FK_705D16B7166D1F9C');
        $this->addSql('ALTER TABLE reimbursement DROP FOREIGN KEY FK_705D16B7229E70A7');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE expense_event');
        $this->addSql('DROP TABLE petty_cash_cycle');
        $this->addSql('DROP TABLE reimbursement');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBF395DB7B');
        $this->addSql('DROP INDEX IDX_795FD9BBF395DB7B ON attachment');
        $this->addSql('ALTER TABLE attachment DROP expense_id');
        $this->addSql('ALTER TABLE fund_movement DROP FOREIGN KEY FK_FB3168E8D4B6C349');
        $this->addSql('DROP INDEX IDX_FB3168E8D4B6C349 ON fund_movement');
        $this->addSql('ALTER TABLE fund_movement DROP petty_cash_cycle_id');
    }
}
