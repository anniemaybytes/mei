<?php

declare(strict_types=1);

namespace Mei\Entity;

use DateTime;

/**
 * Class FilesMap
 *
 * @property string $Key
 * @property string $FileName
 * @property DateTime $UploadTime
 * @property int $Protected
 * @package Mei\Entity
 */
final class FilesMap extends Entity
{
    protected static array $columns = [
        ['Key', 'string', null],
        ['FileName', 'string', null, true],
        ['UploadTime', 'datetime', '0000-00-00 00:00:00'],
        ['Protected', 'int', 0],
    ];
}
