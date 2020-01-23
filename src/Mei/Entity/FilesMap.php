<?php declare(strict_types=1);

namespace Mei\Entity;

use DateTime;

/**
 * Class FilesMap
 *
 * @property string $Key
 * @property string $FileName
 * @property int $UploaderId
 * @property int $TorrentId
 * @property DateTime $UploadTime
 * @property int $Protected
 * @package Mei\Entity
 */
final class FilesMap extends Entity
{
    protected static $columns = [
        ['Key', 'string', null], // key is unique multi to single mapper
        ['FileName', 'string', null, true],
        ['UploaderId', 'int', 0],
        ['TorrentId', 'int', 0],
        ['UploadTime', 'datetime', '0000-00-00 00:00:00'],
        ['Protected', 'int', 0],
    ];
}
