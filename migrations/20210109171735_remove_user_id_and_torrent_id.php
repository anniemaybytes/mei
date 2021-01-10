<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Class RemoveUserIdAndTorrentId
 */
class RemoveUserIdAndTorrentId extends AbstractMigration
{
    public function change(): void
    {
        $this->table('files_map')
            ->removeColumn('UploaderId')
            ->removeColumn('TorrentId')
            ->update();
    }
}
