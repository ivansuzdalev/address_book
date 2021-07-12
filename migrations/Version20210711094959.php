<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210711094959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        

        $command = 'CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY,
                date datetime,
                title  VARCHAR (255) NOT NULL,
                phone  VARCHAR (255) NOT NULL,
                fullname  VARCHAR (255) NOT NULL,
                address  TEXT NOT NULL,
                comment TEXT NOT NULL, 
                user_id INTEGER NOT NULL, 
                FOREIGN KEY (user_id)
                REFERENCES user(id) ON UPDATE CASCADE
                                                ON DELETE CASCADE)';
        // execute the sql commands to create new tables
        $this->addSql($command);

        $command = 'CREATE TABLE IF NOT EXISTS contacts_sharing (
            user_id integer not null,
            contact_id  integer not null,
            foreign key (user_id) references user(id),
            foreign key (contact_id) references contacts(id),
            primary key (user_id, contact_id)
        )';
        // execute the sql commands to create new tables
        $this->addSql($command);

    }

    public function down(Schema $schema): void
    {
        $command = 
            'DROP TABLE user'
          ;
        // execute the sql commands to create new tables
        $this->addSql($command);

        $command = 
            'DROP TABLE contacts'
          ;
        // execute the sql commands to create new tables
        $this->addSql($command);

        $command = 
            'DROP TABLE contacts_sharing'
        ;
        // execute the sql commands to create new tables
        $this->addSql($command);
    }
}
