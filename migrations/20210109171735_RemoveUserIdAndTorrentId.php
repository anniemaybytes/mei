<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Class RemoveUserIdAndTorrentId
 */
final class RemoveUserIdAndTorrentId extends AbstractMigration
{
    public function up(): void
    {
        $this->table('files_map')
            ->removeColumn('UploaderId')
            ->removeColumn('TorrentId')
            ->update();
    }

    public function down(): void
    {
        $this->table('files_map')
            ->addColumn('UploaderId', 'integer', ['null' => false])
            ->addColumn('TorrentId', 'integer', ['null' => false])
            ->update();
    }
}
