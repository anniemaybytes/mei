<?php
namespace Mei\Exception;

use Exception;

class GeneralException extends Exception
{
    private $description;

    public function __construct($description)
    {
        $this->description = $description;
        parent::__construct();
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
