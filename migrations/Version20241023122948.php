<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241023122948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Déplace la propriété avatar de Member vers User';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD avatar VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE user
            SET avatar = (
                SELECT avatar
                FROM member
                WHERE member.user_id = user.id
            )
        ');
        $this->addSql('ALTER TABLE member DROP avatar');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE member ADD avatar VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE member
            SET avatar = (
                SELECT avatar
                FROM user
                WHERE member.user_id = user.id
            )
        ');
        $this->addSql('ALTER TABLE user DROP avatar');
    }
}
