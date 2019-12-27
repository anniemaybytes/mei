<?php

namespace Mei\Model;

use Mei\Entity\IEntity;

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
     * @param $key
     *
     * @return IEntity
     */
    public function getByKey($key)
    {
        return $this->getById(['Key' => $key]);
    }

    /**
     * @param $key
     *
     * @return IEntity
     */
    public function getByFileName($key)
    {
        return $this->getById(['FileName' => $key]);
    }
}
