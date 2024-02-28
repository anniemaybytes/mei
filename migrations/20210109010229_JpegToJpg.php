<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Class JpegToJpg
 */
final class JpegToJpg extends AbstractMigration
{
    public function up(): void
    {
        $sth = $this->adapter->getConnection()->query(
            "SELECT DISTINCT `Key` FROM `files_map` WHERE `Key` LIKE '%.jpeg'"
        );

        while ($key = $sth->fetch(PDO::FETCH_COLUMN)) {
            $p = pathinfo($key);
            $this->execute("UPDATE `files_map` SET `Key` = '{$p['filename']}.jpg' WHERE `Key` = '$key'");
        }
    }
}
