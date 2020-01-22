<?php declare(strict_types=1);

namespace Mei\Model;

/**
 * Class FilesMap
 *
 * @method \Mei\Entity\FilesMap|null getById(?array $id)
 * @method \Mei\Entity\FilesMap|null save(\Mei\Entity\FilesMap $entity)
 * @method \Mei\Entity\FilesMap createEntity(array $arr)
 * @package Mei\Model
 */
class FilesMap extends Model
{
    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'files_map';
    }

    /**
     * @param string $key
     *
     * @return \Mei\Entity\FilesMap
     */
    public function getByKey(string $key): ?\Mei\Entity\FilesMap
    {
        return $this->getById(['Key' => $key]);
    }

    /**
     * @param string $key
     *
     * @return \Mei\Entity\FilesMap
     */
    public function getByFileName(string $key): ?\Mei\Entity\FilesMap
    {
        return $this->getById(['FileName' => $key]);
    }
}
