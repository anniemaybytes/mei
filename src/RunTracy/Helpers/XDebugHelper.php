<?php declare(strict_types=1);

namespace RunTracy\Helpers;

use Tracy\IBarPanel;

/**
 * Class XDebugHelper
 *
 * @package RunTracy\Helpers
 */
class XDebugHelper implements IBarPanel
{
    /**
     * @var string
     */
    protected $ideKey;

    /**
     * XDebugHelper constructor.
     *
     * @param string $ideKey
     */
    public function __construct(string $ideKey = 'RUNTRACY')
    {
        $this->ideKey = $ideKey;
    }

    /**
     * @return string
     */
    public function getPanel(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getTab(): string
    {
        ob_start();
        require __DIR__ . '../../Templates/XDebugHelperTab.phtml';
        return ob_get_clean();
    }
}
