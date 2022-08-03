<?php
/**
 * 테이블 추가/변경/삭제 시 IF NOT EXIST 사용
 * 컬럼 추가/변경 시 parent::existColumn 사용
 */
class V551AddIcodeToken extends Migration
{
    public function up()
    {
        global $g5;

        if (!parent::existColumn($g5['config_table'], "cf_cert_find")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` ADD `cf_cert_find` tinyint(4) NOT NULL DEFAULT '0'");
        }
        if (!parent::existColumn($g5['config_table'], "cf_cert_simple")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` ADD `cf_cert_simple` varchar(255) NOT NULL DEFAULT ''");
        }
        if (!parent::existColumn($g5['config_table'], "cf_cert_kg_cd")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` ADD `cf_cert_kg_cd` varchar(255) NOT NULL DEFAULT ''");
        }
        if (!parent::existColumn($g5['config_table'], "cf_cert_kg_mid")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` ADD `cf_cert_kg_mid` varchar(255) NOT NULL DEFAULT ''");
        }

        $createTableQuery = "CREATE TABLE IF NOT EXISTS `g5_member_cert_history` (
            `ch_id` int(11) NOT NULL auto_increment,
            `mb_id` varchar(20) NOT NULL DEFAULT '',
            `ch_name` varchar(255) NOT NULL DEFAULT '',
            `ch_hp` varchar(255) NOT NULL DEFAULT '',
            `ch_birth` varchar(255) NOT NULL DEFAULT '',
            `ch_type` varchar(20) NOT NULL DEFAULT '',
            `ch_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
            PRIMARY KEY (`ch_id`),
            KEY `mb_id` (`mb_id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        parent::executeQuery($createTableQuery);
    }

    public function down()
    {
        global $g5;
        
        if (parent::existColumn($g5['config_table'], "cf_cert_find")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` DROP COLUMN `cf_cert_find`");
        }
        if (parent::existColumn($g5['config_table'], "cf_cert_simple")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` DROP COLUMN `cf_cert_simple`");
        }
        if (parent::existColumn($g5['config_table'], "cf_cert_kg_cd")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` DROP COLUMN `cf_cert_kg_cd`");
        }
        if (parent::existColumn($g5['config_table'], "cf_cert_kg_mid")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` DROP COLUMN `cf_cert_kg_mid`");
        }

        $dropTableQuery = "DROP TABLE IF EXISTS `g5_member_cert_history`";
        parent::executeQuery($dropTableQuery);
        
    }
}
