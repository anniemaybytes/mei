<?php

namespace Mei\Model;

class FilesMap extends Model
{
    public function getTableName()
    {
        return 'files_map';
    }

    public function getByKey($key)
    {
        return $this->getById(['Key' => $key]);
    }

    public function getByFileName($key)
    {
        return $this->getById(['FileName' => $key]);
    }
}
