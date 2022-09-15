<?php
/**
 * v5.5.8.2.1 데이터베이스 마이그레이션
 */
class V55821AddPollUse extends Migration
{
    public function up()
    {
        global $g5;

        if (!parent::existColumn($g5['poll_table'], "po_use")) {
            parent::executeQuery("ALTER TABLE `{$g5['poll_table']}` add `po_use` tinyint not null default '0' after `mb_ids`");
        }
    }

    public function down()
    {
        /*
        global $g5;

        if (parent::existColumn($g5['poll_table'], "po_use")) {
            parent::executeQuery("ALTER TABLE `{$g5['poll_table']}` DROP COLUMN `po_use`");
        }
        */
    }
}
