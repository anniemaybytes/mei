<?php

namespace Mei\Entity;

class FilesMap extends Entity
{
    protected static $columns = [
        ['Key', 'string', ''], // key is unique multi to single mapper
        ['FileName', 'string', '', true],
        ['UploaderId', 'int', '0'],
        ['TorrentId', 'int', '0'],
        ['UploadTime', 'datetime', '0000-00-00 00:00:00'],
        ['Protected', 'int', '0'],
    ];
}
