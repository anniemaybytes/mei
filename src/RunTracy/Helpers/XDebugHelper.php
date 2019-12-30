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
     * @return bool
     */
    public function getPanel(): bool
    {
        return false;
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
