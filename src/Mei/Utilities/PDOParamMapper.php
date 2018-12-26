<?php
namespace Mei\Utilities;

class PDOParamMapper
{
    public static function map($entityAttrType)
    {
       switch($entityAttrType)
       {
           case 'int': return \PDO::PARAM_INT;
           case 'bool': return \PDO::PARAM_BOOL;
           default: return \PDO::PARAM_STR;
       }
    }
}
