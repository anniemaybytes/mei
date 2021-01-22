<?php

declare(strict_types=1);

namespace Mei\Model;

/**
 * Class FilesMap
 *
 * @method \Mei\Entity\FilesMap|null getById(?array $id)
 * @method \Mei\Entity\FilesMap|null save(\Mei\Entity\FilesMap $entity)
 * @method \Mei\Entity\FilesMap createEntity(array $arr)
 * @package Mei\Model
 */
final class FilesMap extends Model
{
    public function getTableName(): string
    {
        return 'files_map';
    }

    public function getByKey(string $key): ?\Mei\Entity\FilesMap
    {
        return $this->getById(['Key' => $key]);
    }

    public function getByFileName(string $key): ?\Mei\Entity\FilesMap
    {
        return $this->getById(['FileName' => $key]);
    }
}
