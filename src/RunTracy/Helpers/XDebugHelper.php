<?php

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
    public function __construct($ideKey = 'RUNTRACY')
    {
        $this->ideKey = $ideKey;
    }

    /**
     * @return bool|string
     */
    public function getPanel()
    {
        return false;
    }

    /**
     * @return false|string
     */
    public function getTab()
    {
        ob_start();
        require __DIR__ . '../../Templates/XDebugHelperTab.phtml';
        return ob_get_clean();
    }
}
