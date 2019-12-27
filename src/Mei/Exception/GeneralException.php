<?php

namespace Mei\Exception;

use Exception;

/**
 * Class GeneralException
 *
 * @package Mei\Exception
 */
class GeneralException extends Exception
{
    private $description;

    /**
     * GeneralException constructor.
     *
     * @param $description
     */
    public function __construct($description)
    {
        $this->description = $description;
        parent::__construct();
    }

    /**
     * @param $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }
}
