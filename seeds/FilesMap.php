<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Class FilesMap
 */
class FilesMap extends AbstractSeed
{
    public function run(): void
    {
        $this->table('files_map', ['id' => false, 'primary_key' => ['FileName']])
            ->addColumn('Key', 'string', ['limit' => 199, 'null' => false])
            ->addColumn('FileName', 'string', ['limit' => 199, 'null' => false])
            ->addColumn('UploadTime', 'datetime', ['null' => false])
            ->addColumn('Protected', 'integer', ['null' => false])
            ->addColumn('UploaderId', 'integer', ['null' => false])
            ->addColumn('TorrentId', 'integer', ['null' => false])
            ->addIndex(['Key'], ['unique' => false, 'name' => 'Key'])
            ->create();
    }
}
