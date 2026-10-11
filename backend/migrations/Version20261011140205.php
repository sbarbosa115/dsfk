<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261011140205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Budget amendments (Adiciones): new stages on an approved budget, approved apart';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE budget_amendment (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, approved_at DATETIME DEFAULT NULL, number INT NOT NULL, created_at DATETIME NOT NULL, budget_id INT NOT NULL, UNIQUE INDEX uniq_amendment_number (budget_id, number), INDEX IDX_9B17957F36ABA6B8 (budget_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE budget_amendment ADD CONSTRAINT FK_9B17957F36ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE budget_event ADD amendment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE budget_event ADD CONSTRAINT FK_50BF9F514DAB577D FOREIGN KEY (amendment_id) REFERENCES budget_amendment (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_50BF9F514DAB577D ON budget_event (amendment_id)');
        $this->addSql('ALTER TABLE stage ADD amendment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C93694DAB577D FOREIGN KEY (amendment_id) REFERENCES budget_amendment (id)');
        $this->addSql('CREATE INDEX IDX_C27C93694DAB577D ON stage (amendment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE budget_amendment DROP FOREIGN KEY FK_9B17957F36ABA6B8');
        $this->addSql('DROP TABLE budget_amendment');
        $this->addSql('ALTER TABLE budget_event DROP FOREIGN KEY FK_50BF9F514DAB577D');
        $this->addSql('DROP INDEX IDX_50BF9F514DAB577D ON budget_event');
        $this->addSql('ALTER TABLE budget_event DROP amendment_id');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C93694DAB577D');
        $this->addSql('DROP INDEX IDX_C27C93694DAB577D ON stage');
        $this->addSql('ALTER TABLE stage DROP amendment_id');
    }
}
