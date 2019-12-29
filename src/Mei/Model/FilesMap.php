<?php declare(strict_types=1);

namespace Mei\Model;

/**
 * Class FilesMap
 *
 * @package Mei\Model
 */
class FilesMap extends Model
{
    /**
     * @return string
     */
    public function getTableName()
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
     * @param $key
     *
     * @return \Mei\Entity\FilesMap
     */
    public function getByFileName($key): ?\Mei\Entity\FilesMap
    {
        return $this->getById(['FileName' => $key]);
    }
}
