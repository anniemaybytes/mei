<?php

declare(strict_types=1);

use Phpmig\Migration\Migration;

/**
 * Class JpegToJpg
 */
class JpegToJpg extends Migration
{
    public function up(): void
    {
        $sth = $this->get(PDO::class)->query("SELECT DISTINCT `Key` FROM `files_map` WHERE `Key` LIKE '%.jpeg'");

        while ($key = $sth->fetch(PDO::FETCH_COLUMN)) {
            $p = pathinfo($key);
            $this->get(PDO::class)->exec("UPDATE `files_map` SET `Key` = '{$p['filename']}.jpg' WHERE `Key` = '$key'");
        }
    }
}
