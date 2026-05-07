<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507175053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // NOTE: seq_log is managed at runtime by SequenceGenerator (audit log references).
        // Doctrine's diff falsely flags it as orphaned — never drop/recreate it.
        $this->addSql('CREATE SEQUENCE seq_project_task_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE seq_project_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE core_project_tasks (id INT NOT NULL, reference VARCHAR(32) DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'todo\' NOT NULL, priority VARCHAR(20) DEFAULT \'medium\' NOT NULL, due_date DATE DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id INT NOT NULL, assignee_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBA88656AEA34913 ON core_project_tasks (reference)');
        $this->addSql('CREATE INDEX IDX_CBA88656166D1F9C ON core_project_tasks (project_id)');
        $this->addSql('CREATE INDEX IDX_CBA8865659EC7D60 ON core_project_tasks (assignee_id)');
        $this->addSql('CREATE TABLE core_projects (id INT NOT NULL, reference VARCHAR(32) DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, responsible_user_id INT DEFAULT NULL, crm_contact_id INT DEFAULT NULL, crm_company_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E351C507AEA34913 ON core_projects (reference)');
        $this->addSql('CREATE INDEX IDX_E351C507BDAD1998 ON core_projects (responsible_user_id)');
        $this->addSql('CREATE INDEX IDX_E351C507A23F13C2 ON core_projects (crm_contact_id)');
        $this->addSql('CREATE INDEX IDX_E351C507D2052C5E ON core_projects (crm_company_id)');
        $this->addSql('ALTER TABLE core_project_tasks ADD CONSTRAINT FK_CBA88656166D1F9C FOREIGN KEY (project_id) REFERENCES core_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_project_tasks ADD CONSTRAINT FK_CBA8865659EC7D60 FOREIGN KEY (assignee_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT FK_E351C507BDAD1998 FOREIGN KEY (responsible_user_id) REFERENCES core_users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT FK_E351C507A23F13C2 FOREIGN KEY (crm_contact_id) REFERENCES core_crm_contacts (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE core_projects ADD CONSTRAINT FK_E351C507D2052C5E FOREIGN KEY (crm_company_id) REFERENCES core_crm_companies (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER INDEX uniq_16901760aea34913 RENAME TO UNIQ_6B6CD538AEA34913');
        $this->addSql('ALTER INDEX uniq_169017605f37a13b RENAME TO UNIQ_6B6CD5385F37A13B');
        $this->addSql('ALTER INDEX uniq_d62f2858aea34913 RENAME TO UNIQ_EA63ACB9AEA34913');
        $this->addSql('ALTER INDEX idx_bfb98dfb2989f1fd RENAME TO IDX_5C28EEC62989F1FD');
        $this->addSql('ALTER INDEX idx_72f3466968b77723 RENAME TO IDX_173E636D68B77723');
        $this->addSql('ALTER INDEX idx_72f34669d1710f83 RENAME TO IDX_173E636DD1710F83');
        $this->addSql('ALTER INDEX uniq_72f346691c696f7a RENAME TO UNIQ_173E636D1C696F7A');
        $this->addSql('ALTER INDEX idx_72f34669c33f7837 RENAME TO IDX_173E636DC33F7837');
        $this->addSql('ALTER INDEX idx_72f3466927426a53 RENAME TO IDX_173E636D27426A53');
        $this->addSql('ALTER INDEX uniq_bb1514f3aea34913 RENAME TO UNIQ_DED831F7AEA34913');
        $this->addSql('ALTER INDEX idx_bb1514f3ea9fdd75 RENAME TO IDX_DED831F7EA9FDD75');
        $this->addSql('ALTER INDEX idx_bb1514f3b03a8386 RENAME TO IDX_DED831F7B03A8386');
        $this->addSql('ALTER INDEX uniq_13381e8faea34913 RENAME TO UNIQ_9B9DF4D0AEA34913');
        $this->addSql('ALTER INDEX idx_13381e8f979b1ad6 RENAME TO IDX_9B9DF4D0979B1AD6');
        $this->addSql('ALTER INDEX idx_d10d9ee5f8697d13 RENAME TO IDX_D60597D9F8697D13');
        $this->addSql('ALTER INDEX uniq_5f9e962aaea34913 RENAME TO UNIQ_E05CE089AEA34913');
        $this->addSql('ALTER INDEX idx_5f9e962a4b89032c RENAME TO IDX_E05CE0894B89032C');
        $this->addSql('ALTER INDEX idx_5f9e962a727aca70 RENAME TO IDX_E05CE089727ACA70');
        $this->addSql('ALTER INDEX uniq_c32e5717aea34913 RENAME TO UNIQ_4B8BBD48AEA34913');
        $this->addSql('ALTER INDEX uniq_5dc758d8aea34913 RENAME TO UNIQ_F077E100AEA34913');
        $this->addSql('ALTER INDEX idx_5dc758d8979b1ad6 RENAME TO IDX_F077E100979B1AD6');
        $this->addSql('ALTER INDEX uniq_5330dba3aea34913 RENAME TO UNIQ_1C50EB87AEA34913');
        $this->addSql('ALTER INDEX idx_5330dba3e7a1254a RENAME TO IDX_1C50EB87E7A1254A');
        $this->addSql('ALTER INDEX idx_5330dba3979b1ad6 RENAME TO IDX_1C50EB87979B1AD6');
        $this->addSql('ALTER INDEX uniq_4c2d1439aea34913 RENAME TO UNIQ_BE453BB1AEA34913');
        $this->addSql('ALTER INDEX idx_4c2d14391ad5cdbf RENAME TO IDX_BE453BB11AD5CDBF');
        $this->addSql('ALTER INDEX idx_4c2d1439d4619d1a RENAME TO IDX_BE453BB1D4619D1A');
        $this->addSql('ALTER INDEX uniq_49630c21aea34913 RENAME TO UNIQ_349FCE79AEA34913');
        $this->addSql('ALTER INDEX uniq_10e893baea34913 RENAME TO UNIQ_2E66FDB5AEA34913');
        $this->addSql('ALTER INDEX uniq_10e893b4584665a RENAME TO UNIQ_2E66FDB54584665A');
        $this->addSql('ALTER INDEX idx_10e893b3569d950 RENAME TO IDX_2E66FDB53569D950');
        $this->addSql('ALTER INDEX uniq_95de1cecaea34913 RENAME TO UNIQ_764F7FD1AEA34913');
        $this->addSql('ALTER INDEX idx_95de1cec8d9f6d38 RENAME TO IDX_764F7FD18D9F6D38');
        $this->addSql('ALTER INDEX idx_95de1cecd4619d1a RENAME TO IDX_764F7FD1D4619D1A');
        $this->addSql('ALTER INDEX idx_762161359395c3f3 RENAME TO IDX_13EC44319395C3F3');
        $this->addSql('ALTER INDEX idx_e050b8493da5256d RENAME TO IDX_4DE001913DA5256D');
        $this->addSql('ALTER INDEX idx_f759c338443707b0 RENAME TO IDX_490A58F5443707B0');
        $this->addSql('ALTER INDEX uniq_f759c338443707b04180c698 RENAME TO UNIQ_490A58F5443707B04180C698');
        $this->addSql('ALTER INDEX uniq_7c0b3726aea34913 RENAME TO UNIQ_AB3AA94CAEA34913');
        $this->addSql('ALTER INDEX idx_7c0b37265ff69b7d RENAME TO IDX_AB3AA94C5FF69B7D');
        $this->addSql('ALTER INDEX uniq_c80af9e6aea34913 RENAME TO UNIQ_ADC7DCE2AEA34913');
        $this->addSql('ALTER INDEX idx_c80af9e65ff69b7d RENAME TO IDX_ADC7DCE25FF69B7D');
        $this->addSql('ALTER INDEX idx_acf091e55ff69b7d RENAME TO IDX_ABF898D95FF69B7D');
        $this->addSql('ALTER INDEX uniq_acf091e55ff69b7d4180c698 RENAME TO UNIQ_ABF898D95FF69B7D4180C698');
        $this->addSql('ALTER INDEX uniq_acf091e54180c698989d9b62 RENAME TO UNIQ_ABF898D94180C698989D9B62');
        $this->addSql('ALTER INDEX uniq_fd3f1bf7aea34913 RENAME TO UNIQ_ABBE3A17AEA34913');
        $this->addSql('ALTER INDEX uniq_9327dc57989d9b62 RENAME TO UNIQ_2D74479A989D9B62');
        $this->addSql('ALTER INDEX uniq_20aedfc5aea34913 RENAME TO UNIQ_A80B359AAEA34913');
        $this->addSql('ALTER INDEX idx_20aedfc512469de2 RENAME TO IDX_A80B359A12469DE2');
        $this->addSql('ALTER INDEX idx_20aedfc593cb796c RENAME TO IDX_A80B359A93CB796C');
        $this->addSql('ALTER INDEX uniq_6a2ca10caea34913 RENAME TO UNIQ_3CAD80ECAEA34913');
        $this->addSql('ALTER INDEX idx_6a2ca10c162cb942 RENAME TO IDX_3CAD80EC162CB942');
        $this->addSql('ALTER INDEX idx_6a2ca10ca2b28fe8 RENAME TO IDX_3CAD80ECA2B28FE8');
        $this->addSql('ALTER INDEX uniq_9fe05546aea34913 RENAME TO UNIQ_1745BF19AEA34913');
        $this->addSql('ALTER INDEX idx_24d3f7359ab44fe0 RENAME TO IDX_7C582A479AB44FE0');
        $this->addSql('ALTER INDEX uniq_70b2ca2aaea34913 RENAME TO UNIQ_4CFE4ECBAEA34913');
        $this->addSql('ALTER INDEX idx_70b2ca2a727aca70 RENAME TO IDX_4CFE4ECB727ACA70');
        $this->addSql('ALTER INDEX idx_70b2ca2accd7e912 RENAME TO IDX_4CFE4ECBCCD7E912');
        $this->addSql('ALTER INDEX uniq_727508cf5e9e89cb RENAME TO UNIQ_24F4292F5E9E89CB');
        $this->addSql('ALTER INDEX uniq_faf03896aea34913 RENAME TO UNIQ_870CFACEAEA34913');
        $this->addSql('ALTER INDEX uniq_faf03896989d9b62 RENAME TO UNIQ_870CFACE989D9B62');
        $this->addSql('ALTER INDEX idx_faf03896329a1b2e RENAME TO IDX_870CFACE329A1B2E');
        $this->addSql('ALTER INDEX idx_faf03896b03a8386 RENAME TO IDX_870CFACEB03A8386');
        $this->addSql('ALTER INDEX idx_faf0389677f5180b RENAME TO IDX_870CFACE77F5180B');
        $this->addSql('ALTER INDEX uniq_a11b9099aea34913 RENAME TO UNIQ_6935614FAEA34913');
        $this->addSql('ALTER INDEX uniq_2ec6357baea34913 RENAME TO UNIQ_CD575646AEA34913');
        $this->addSql('ALTER INDEX uniq_7f50871eaea34913 RENAME TO UNIQ_B77E76C8AEA34913');
        $this->addSql('ALTER INDEX uniq_81b213b9aea34913 RENAME TO UNIQ_8B9DD5EAAEA34913');
        $this->addSql('ALTER INDEX idx_81b213b94e7af8f RENAME TO IDX_8B9DD5EA4E7AF8F');
        $this->addSql('ALTER INDEX idx_81b213b9ea9fdd75 RENAME TO IDX_8B9DD5EAEA9FDD75');
        $this->addSql('ALTER INDEX uniq_1ce6b23caea34913 RENAME TO UNIQ_16C9746FAEA34913');
        $this->addSql('ALTER INDEX idx_1ce6b23c2a151376 RENAME TO IDX_16C9746F2A151376');
        $this->addSql('ALTER INDEX idx_28df5c724b89032c RENAME TO IDX_D383B5FD4B89032C');
        $this->addSql('ALTER INDEX idx_28df5c72f675f31b RENAME TO IDX_D383B5FDF675F31B');
        $this->addSql('ALTER INDEX idx_6d8aa7546efcb8b8 RENAME TO IDX_6A82AE686EFCB8B8');
        $this->addSql('ALTER INDEX idx_6d8aa7544b89032c RENAME TO IDX_6A82AE684B89032C');
        $this->addSql('ALTER INDEX uniq_6d8aa7544b89032c4180c698 RENAME TO UNIQ_6A82AE684B89032C4180C698');
        $this->addSql('ALTER INDEX idx_b172029ff8a43ba0 RENAME TO IDX_D4BF279BF8A43BA0');
        $this->addSql('ALTER INDEX uniq_ce2b05df989d9b62 RENAME TO UNIQ_F267813E989D9B62');
        $this->addSql('ALTER INDEX idx_731e96b4f8a43ba0 RENAME TO IDX_8176B93CF8A43BA0');
        $this->addSql('ALTER INDEX idx_731e96b49557e6f6 RENAME TO IDX_8176B93C9557E6F6');
        $this->addSql('ALTER INDEX uniq_885dbafaaea34913 RENAME TO UNIQ_DEDC9B1AAEA34913');
        $this->addSql('ALTER INDEX idx_885dbafaf8a43ba0 RENAME TO IDX_DEDC9B1AF8A43BA0');
        $this->addSql('ALTER INDEX idx_885dbafae2532148 RENAME TO IDX_DEDC9B1AE2532148');
        $this->addSql('ALTER INDEX idx_885dbafaf675f31b RENAME TO IDX_DEDC9B1AF675F31B');
        $this->addSql('ALTER INDEX idx_1fb9b39e4b89032c RENAME TO IDX_23F5377F4B89032C');
        $this->addSql('ALTER INDEX idx_1fb9b39e898ca496 RENAME TO IDX_23F5377F898CA496');
        $this->addSql('ALTER INDEX idx_e7ade2c24b89032c RENAME TO IDX_C8C5964C4B89032C');
        $this->addSql('ALTER INDEX idx_e7ade2c27490c989 RENAME TO IDX_C8C5964C7490C989');
        $this->addSql('ALTER INDEX uniq_16646b41aea34913 RENAME TO UNIQ_A837F08CAEA34913');
        $this->addSql('ALTER INDEX idx_16646b41a76ed395 RENAME TO IDX_A837F08CA76ED395');
        $this->addSql('ALTER INDEX uniq_232b80f9989d9b62 RENAME TO UNIQ_1F670418989D9B62');
        $this->addSql('ALTER INDEX idx_67f9d347e2c35fc RENAME TO IDX_9E598575E2C35FC');
        $this->addSql('ALTER INDEX uniq_67f9d347e2c35fc4180c698 RENAME TO UNIQ_9E598575E2C35FC4180C698');
        $this->addSql('ALTER INDEX uniq_ddc5fbdcaea34913 RENAME TO UNIQ_26991253AEA34913');
        $this->addSql('ALTER INDEX idx_ddc5fbdc9557e6f6 RENAME TO IDX_269912539557E6F6');
        $this->addSql('ALTER INDEX idx_ddc5fbdc727aca70 RENAME TO IDX_26991253727ACA70');
        $this->addSql('ALTER INDEX idx_bd7360469557e6f6 RENAME TO IDX_5EE2037B9557E6F6');
        $this->addSql('ALTER INDEX uniq_bd7360469557e6f64180c698 RENAME TO UNIQ_5EE2037B9557E6F64180C698');
        $this->addSql('ALTER INDEX uniq_154232de989d9b62 RENAME TO UNIQ_B51E5187989D9B62');
        $this->addSql('ALTER INDEX uniq_1483a5e9aea34913 RENAME TO UNIQ_42028409AEA34913');
        $this->addSql('ALTER INDEX uniq_1483a5e9b6869ac0 RENAME TO UNIQ_42028409B6869AC0');
        $this->addSql('ALTER INDEX uniq_1483a5e9c4995c67 RENAME TO UNIQ_42028409C4995C67');
        $this->addSql('ALTER INDEX idx_1483a5e9783e3463 RENAME TO IDX_42028409783E3463');
        $this->addSql('ALTER INDEX idx_1483a5e9cdeadb2a RENAME TO IDX_42028409CDEADB2A');
        $this->addSql('ALTER INDEX idx_1483a5e9ed5ca9e6 RENAME TO IDX_42028409ED5CA9E6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE seq_project_task_id CASCADE');
        $this->addSql('DROP SEQUENCE seq_project_id CASCADE');
        $this->addSql('ALTER TABLE core_project_tasks DROP CONSTRAINT FK_CBA88656166D1F9C');
        $this->addSql('ALTER TABLE core_project_tasks DROP CONSTRAINT FK_CBA8865659EC7D60');
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT FK_E351C507BDAD1998');
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT FK_E351C507A23F13C2');
        $this->addSql('ALTER TABLE core_projects DROP CONSTRAINT FK_E351C507D2052C5E');
        $this->addSql('DROP TABLE core_project_tasks');
        $this->addSql('DROP TABLE core_projects');
        $this->addSql('ALTER INDEX uniq_6b6cd538aea34913 RENAME TO uniq_16901760aea34913');
        $this->addSql('ALTER INDEX uniq_6b6cd5385f37a13b RENAME TO uniq_169017605f37a13b');
        $this->addSql('ALTER INDEX uniq_ea63acb9aea34913 RENAME TO uniq_d62f2858aea34913');
        $this->addSql('ALTER INDEX idx_5c28eec62989f1fd RENAME TO idx_bfb98dfb2989f1fd');
        $this->addSql('ALTER INDEX idx_173e636d68b77723 RENAME TO idx_72f3466968b77723');
        $this->addSql('ALTER INDEX idx_173e636d27426a53 RENAME TO idx_72f3466927426a53');
        $this->addSql('ALTER INDEX idx_173e636dc33f7837 RENAME TO idx_72f34669c33f7837');
        $this->addSql('ALTER INDEX uniq_173e636d1c696f7a RENAME TO uniq_72f346691c696f7a');
        $this->addSql('ALTER INDEX idx_173e636dd1710f83 RENAME TO idx_72f34669d1710f83');
        $this->addSql('ALTER INDEX uniq_ded831f7aea34913 RENAME TO uniq_bb1514f3aea34913');
        $this->addSql('ALTER INDEX idx_ded831f7ea9fdd75 RENAME TO idx_bb1514f3ea9fdd75');
        $this->addSql('ALTER INDEX idx_ded831f7b03a8386 RENAME TO idx_bb1514f3b03a8386');
        $this->addSql('ALTER INDEX idx_9b9df4d0979b1ad6 RENAME TO idx_13381e8f979b1ad6');
        $this->addSql('ALTER INDEX uniq_9b9df4d0aea34913 RENAME TO uniq_13381e8faea34913');
        $this->addSql('ALTER INDEX idx_d60597d9f8697d13 RENAME TO idx_d10d9ee5f8697d13');
        $this->addSql('ALTER INDEX idx_e05ce0894b89032c RENAME TO idx_5f9e962a4b89032c');
        $this->addSql('ALTER INDEX uniq_e05ce089aea34913 RENAME TO uniq_5f9e962aaea34913');
        $this->addSql('ALTER INDEX idx_e05ce089727aca70 RENAME TO idx_5f9e962a727aca70');
        $this->addSql('ALTER INDEX uniq_4b8bbd48aea34913 RENAME TO uniq_c32e5717aea34913');
        $this->addSql('ALTER INDEX uniq_f077e100aea34913 RENAME TO uniq_5dc758d8aea34913');
        $this->addSql('ALTER INDEX idx_f077e100979b1ad6 RENAME TO idx_5dc758d8979b1ad6');
        $this->addSql('ALTER INDEX uniq_1c50eb87aea34913 RENAME TO uniq_5330dba3aea34913');
        $this->addSql('ALTER INDEX idx_1c50eb87979b1ad6 RENAME TO idx_5330dba3979b1ad6');
        $this->addSql('ALTER INDEX idx_1c50eb87e7a1254a RENAME TO idx_5330dba3e7a1254a');
        $this->addSql('ALTER INDEX idx_be453bb11ad5cdbf RENAME TO idx_4c2d14391ad5cdbf');
        $this->addSql('ALTER INDEX idx_be453bb1d4619d1a RENAME TO idx_4c2d1439d4619d1a');
        $this->addSql('ALTER INDEX uniq_be453bb1aea34913 RENAME TO uniq_4c2d1439aea34913');
        $this->addSql('ALTER INDEX uniq_349fce79aea34913 RENAME TO uniq_49630c21aea34913');
        $this->addSql('ALTER INDEX idx_2e66fdb53569d950 RENAME TO idx_10e893b3569d950');
        $this->addSql('ALTER INDEX uniq_2e66fdb54584665a RENAME TO uniq_10e893b4584665a');
        $this->addSql('ALTER INDEX uniq_2e66fdb5aea34913 RENAME TO uniq_10e893baea34913');
        $this->addSql('ALTER INDEX idx_764f7fd18d9f6d38 RENAME TO idx_95de1cec8d9f6d38');
        $this->addSql('ALTER INDEX idx_764f7fd1d4619d1a RENAME TO idx_95de1cecd4619d1a');
        $this->addSql('ALTER INDEX uniq_764f7fd1aea34913 RENAME TO uniq_95de1cecaea34913');
        $this->addSql('ALTER INDEX idx_13ec44319395c3f3 RENAME TO idx_762161359395c3f3');
        $this->addSql('ALTER INDEX idx_4de001913da5256d RENAME TO idx_e050b8493da5256d');
        $this->addSql('ALTER INDEX uniq_490a58f5443707b04180c698 RENAME TO uniq_f759c338443707b04180c698');
        $this->addSql('ALTER INDEX idx_490a58f5443707b0 RENAME TO idx_f759c338443707b0');
        $this->addSql('ALTER INDEX uniq_ab3aa94caea34913 RENAME TO uniq_7c0b3726aea34913');
        $this->addSql('ALTER INDEX idx_ab3aa94c5ff69b7d RENAME TO idx_7c0b37265ff69b7d');
        $this->addSql('ALTER INDEX idx_adc7dce25ff69b7d RENAME TO idx_c80af9e65ff69b7d');
        $this->addSql('ALTER INDEX uniq_adc7dce2aea34913 RENAME TO uniq_c80af9e6aea34913');
        $this->addSql('ALTER INDEX uniq_abf898d94180c698989d9b62 RENAME TO uniq_acf091e54180c698989d9b62');
        $this->addSql('ALTER INDEX uniq_abf898d95ff69b7d4180c698 RENAME TO uniq_acf091e55ff69b7d4180c698');
        $this->addSql('ALTER INDEX idx_abf898d95ff69b7d RENAME TO idx_acf091e55ff69b7d');
        $this->addSql('ALTER INDEX uniq_abbe3a17aea34913 RENAME TO uniq_fd3f1bf7aea34913');
        $this->addSql('ALTER INDEX uniq_2d74479a989d9b62 RENAME TO uniq_9327dc57989d9b62');
        $this->addSql('ALTER INDEX idx_a80b359a12469de2 RENAME TO idx_20aedfc512469de2');
        $this->addSql('ALTER INDEX uniq_a80b359aaea34913 RENAME TO uniq_20aedfc5aea34913');
        $this->addSql('ALTER INDEX idx_a80b359a93cb796c RENAME TO idx_20aedfc593cb796c');
        $this->addSql('ALTER INDEX idx_3cad80eca2b28fe8 RENAME TO idx_6a2ca10ca2b28fe8');
        $this->addSql('ALTER INDEX idx_3cad80ec162cb942 RENAME TO idx_6a2ca10c162cb942');
        $this->addSql('ALTER INDEX uniq_3cad80ecaea34913 RENAME TO uniq_6a2ca10caea34913');
        $this->addSql('ALTER INDEX uniq_1745bf19aea34913 RENAME TO uniq_9fe05546aea34913');
        $this->addSql('ALTER INDEX idx_7c582a479ab44fe0 RENAME TO idx_24d3f7359ab44fe0');
        $this->addSql('ALTER INDEX idx_4cfe4ecb727aca70 RENAME TO idx_70b2ca2a727aca70');
        $this->addSql('ALTER INDEX idx_4cfe4ecbccd7e912 RENAME TO idx_70b2ca2accd7e912');
        $this->addSql('ALTER INDEX uniq_4cfe4ecbaea34913 RENAME TO uniq_70b2ca2aaea34913');
        $this->addSql('ALTER INDEX uniq_24f4292f5e9e89cb RENAME TO uniq_727508cf5e9e89cb');
        $this->addSql('ALTER INDEX uniq_870cfaceaea34913 RENAME TO uniq_faf03896aea34913');
        $this->addSql('ALTER INDEX idx_870cface329a1b2e RENAME TO idx_faf03896329a1b2e');
        $this->addSql('ALTER INDEX idx_870cfaceb03a8386 RENAME TO idx_faf03896b03a8386');
        $this->addSql('ALTER INDEX idx_870cface77f5180b RENAME TO idx_faf0389677f5180b');
        $this->addSql('ALTER INDEX uniq_870cface989d9b62 RENAME TO uniq_faf03896989d9b62');
        $this->addSql('ALTER INDEX uniq_6935614faea34913 RENAME TO uniq_a11b9099aea34913');
        $this->addSql('ALTER INDEX uniq_cd575646aea34913 RENAME TO uniq_2ec6357baea34913');
        $this->addSql('ALTER INDEX uniq_b77e76c8aea34913 RENAME TO uniq_7f50871eaea34913');
        $this->addSql('ALTER INDEX idx_8b9dd5eaea9fdd75 RENAME TO idx_81b213b9ea9fdd75');
        $this->addSql('ALTER INDEX uniq_8b9dd5eaaea34913 RENAME TO uniq_81b213b9aea34913');
        $this->addSql('ALTER INDEX idx_8b9dd5ea4e7af8f RENAME TO idx_81b213b94e7af8f');
        $this->addSql('ALTER INDEX uniq_16c9746faea34913 RENAME TO uniq_1ce6b23caea34913');
        $this->addSql('ALTER INDEX idx_16c9746f2a151376 RENAME TO idx_1ce6b23c2a151376');
        $this->addSql('ALTER INDEX idx_c8c5964c4b89032c RENAME TO idx_e7ade2c24b89032c');
        $this->addSql('ALTER INDEX idx_c8c5964c7490c989 RENAME TO idx_e7ade2c27490c989');
        $this->addSql('ALTER INDEX idx_d383b5fd4b89032c RENAME TO idx_28df5c724b89032c');
        $this->addSql('ALTER INDEX idx_d383b5fdf675f31b RENAME TO idx_28df5c72f675f31b');
        $this->addSql('ALTER INDEX idx_23f5377f4b89032c RENAME TO idx_1fb9b39e4b89032c');
        $this->addSql('ALTER INDEX idx_23f5377f898ca496 RENAME TO idx_1fb9b39e898ca496');
        $this->addSql('ALTER INDEX idx_6a82ae686efcb8b8 RENAME TO idx_6d8aa7546efcb8b8');
        $this->addSql('ALTER INDEX uniq_6a82ae684b89032c4180c698 RENAME TO uniq_6d8aa7544b89032c4180c698');
        $this->addSql('ALTER INDEX idx_6a82ae684b89032c RENAME TO idx_6d8aa7544b89032c');
        $this->addSql('ALTER INDEX idx_d4bf279bf8a43ba0 RENAME TO idx_b172029ff8a43ba0');
        $this->addSql('ALTER INDEX idx_8176b93cf8a43ba0 RENAME TO idx_731e96b4f8a43ba0');
        $this->addSql('ALTER INDEX idx_8176b93c9557e6f6 RENAME TO idx_731e96b49557e6f6');
        $this->addSql('ALTER INDEX uniq_f267813e989d9b62 RENAME TO uniq_ce2b05df989d9b62');
        $this->addSql('ALTER INDEX idx_dedc9b1af8a43ba0 RENAME TO idx_885dbafaf8a43ba0');
        $this->addSql('ALTER INDEX uniq_dedc9b1aaea34913 RENAME TO uniq_885dbafaaea34913');
        $this->addSql('ALTER INDEX idx_dedc9b1af675f31b RENAME TO idx_885dbafaf675f31b');
        $this->addSql('ALTER INDEX idx_dedc9b1ae2532148 RENAME TO idx_885dbafae2532148');
        $this->addSql('ALTER INDEX idx_a837f08ca76ed395 RENAME TO idx_16646b41a76ed395');
        $this->addSql('ALTER INDEX uniq_a837f08caea34913 RENAME TO uniq_16646b41aea34913');
        $this->addSql('ALTER INDEX uniq_1f670418989d9b62 RENAME TO uniq_232b80f9989d9b62');
        $this->addSql('ALTER INDEX uniq_9e598575e2c35fc4180c698 RENAME TO uniq_67f9d347e2c35fc4180c698');
        $this->addSql('ALTER INDEX idx_9e598575e2c35fc RENAME TO idx_67f9d347e2c35fc');
        $this->addSql('ALTER INDEX uniq_26991253aea34913 RENAME TO uniq_ddc5fbdcaea34913');
        $this->addSql('ALTER INDEX idx_269912539557e6f6 RENAME TO idx_ddc5fbdc9557e6f6');
        $this->addSql('ALTER INDEX idx_26991253727aca70 RENAME TO idx_ddc5fbdc727aca70');
        $this->addSql('ALTER INDEX idx_5ee2037b9557e6f6 RENAME TO idx_bd7360469557e6f6');
        $this->addSql('ALTER INDEX uniq_5ee2037b9557e6f64180c698 RENAME TO uniq_bd7360469557e6f64180c698');
        $this->addSql('ALTER INDEX uniq_b51e5187989d9b62 RENAME TO uniq_154232de989d9b62');
        $this->addSql('ALTER INDEX uniq_42028409b6869ac0 RENAME TO uniq_1483a5e9b6869ac0');
        $this->addSql('ALTER INDEX idx_42028409cdeadb2a RENAME TO idx_1483a5e9cdeadb2a');
        $this->addSql('ALTER INDEX idx_42028409ed5ca9e6 RENAME TO idx_1483a5e9ed5ca9e6');
        $this->addSql('ALTER INDEX uniq_42028409aea34913 RENAME TO uniq_1483a5e9aea34913');
        $this->addSql('ALTER INDEX idx_42028409783e3463 RENAME TO idx_1483a5e9783e3463');
        $this->addSql('ALTER INDEX uniq_42028409c4995c67 RENAME TO uniq_1483a5e9c4995c67');
    }
}
