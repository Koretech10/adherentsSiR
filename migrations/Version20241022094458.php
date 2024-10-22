<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241022094458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les relations entre User et Member/Partner';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE member ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78A76ED395 ON member (user_id)');
        $this->addSql('ALTER TABLE partner ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_312B3E16A76ED395 ON partner (user_id)');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395');
        $this->addSql('DROP INDEX UNIQ_70E4FA78A76ED395 ON member');
        $this->addSql('ALTER TABLE member DROP user_id');
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E16A76ED395');
        $this->addSql('DROP INDEX UNIQ_312B3E16A76ED395 ON partner');
        $this->addSql('ALTER TABLE partner DROP user_id');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }
}
