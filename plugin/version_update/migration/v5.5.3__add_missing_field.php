<?php
/**
 * 테이블 추가/변경/삭제 시 IF NOT EXIST 사용
 * 컬럼 추가/변경 시 parent::existColumn 사용
 */
class V553AddMissingField extends Migration
{
    public function up()
    {
        global $g5;

        if (!parent::existColumn($g5['config_table'], "cf_icode_token_key")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` ADD `cf_icode_token_key` varchar(100) NOT NULL DEFAULT ''");
        }
        if (!parent::existColumn($g5['g5_shop_default_table'], "de_easy_pay_services")) {
            parent::executeQuery("ALTER TABLE `{$g5['g5_shop_default_table']}` ADD `de_easy_pay_services` varchar(255) NOT NULL DEFAULT ''");
        }
    }

    public function down()
    {
        global $g5;

        if (parent::existColumn($g5['config_table'], "cf_icode_token_key")) {
            parent::executeQuery("ALTER TABLE `{$g5['config_table']}` DROP COLUMN `cf_icode_token_key`");
        }
        if (parent::existColumn($g5['g5_shop_default_table'], "de_easy_pay_services")) {
            parent::executeQuery("ALTER TABLE `{$g5['g5_shop_default_table']}` DROP COLUMN `de_easy_pay_services`");
        }
    }
}
