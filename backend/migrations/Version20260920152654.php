<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260920152654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Super admin flag: the level of admin that may "view as" another user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD super_admin TINYINT(1) NOT NULL DEFAULT 0');
        // Existing admins keep the "Ver como" access they have today; the flag can then be
        // taken away per user from Usuarios. Without this nobody could grant it to anyone,
        // because only a super admin may hand it out.
        $this->addSql('UPDATE `user` SET super_admin = 1 WHERE admin = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP super_admin');
    }
}
