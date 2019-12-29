<?php declare(strict_types=1);

namespace Mei\Exception;

use Exception;

/**
 * Class GeneralException
 *
 * @package Mei\Exception
 */
class GeneralException extends Exception
{
    /** @var string|null $description */
    private $description;

    /**
     * GeneralException constructor.
     *
     * @param $description
     */
    public function __construct(?string $description = null)
    {
        $this->description = $description;
        parent::__construct();
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
