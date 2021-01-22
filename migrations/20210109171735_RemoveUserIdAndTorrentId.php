<?php

declare(strict_types=1);

use Phpmig\Migration\Migration;

/**
 * Class RemoveUserIdAndTorrentId
 */
class RemoveUserIdAndTorrentId extends Migration
{
    public function up(): void
    {
        $this->get(PDO::class)->exec('ALTER TABLE `files_map` DROP COLUMN `UploaderId`');
        $this->get(PDO::class)->exec('ALTER TABLE `files_map` DROP COLUMN `TorrentId`');
    }

    public function down(): void
    {
        $this->get(PDO::class)->exec('ALTER TABLE `files_map` ADD COLUMN `UploaderId` INT(11) NOT NULL');
        $this->get(PDO::class)->exec('ALTER TABLE `files_map` ADD COLUMN `TorrentId` INT(11) NOT NULL');
    }
}
