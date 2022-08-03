<?php
/**
 * 테이블 추가/변경/삭제 시 IF NOT EXIST 사용
 * 컬럼 추가/변경 시 parent::existColumn 사용
 */
class V5583AddPollUse extends Migration
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
        global $g5;

        if (parent::existColumn($g5['poll_table'], "po_use")) {
            parent::executeQuery("ALTER TABLE `{$g5['poll_table']}` DROP COLUMN `po_use`");
        }
    }
}
