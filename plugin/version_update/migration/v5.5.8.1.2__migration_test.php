<?php
/**
 * 테이블 추가/변경/삭제 시 IF NOT EXIST 사용
 * 컬럼 추가/변경 시 parent::existColumn 사용
 */
class V55812MigrationTest extends Migration
{
    public function up()
    { 
        if (!parent::existColumn("g5_migrations", "v55812")) {
            parent::executeQuery("ALTER TABLE `g5_migrations` ADD `v55812` varchar(255) NOT NULL DEFAULT ''");
        }
    }

    public function down()
    {
        if (parent::existColumn("g5_migrations", "v55812")) {
            parent::executeQuery("ALTER TABLE `g5_migrations` DROP COLUMN `v55812`");
        }
    }
}
