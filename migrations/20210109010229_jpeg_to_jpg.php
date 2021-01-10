<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Class JpegToJpg
 */
class JpegToJpg extends AbstractMigration
{
    public function up(): void
    {
        $sth = $this->adapter->getConnection()->query(
            "select distinct `Key` from `files_map` where `Key` like '%.jpeg'"
        );

        while ($key = $sth->fetch(PDO::FETCH_COLUMN)) {
            $p = pathinfo($key);
            $this->execute("update `files_map` set `Key` = '{$p['filename']}.jpg' WHERE `Key` = '{$key}'");
        }
    }
}
