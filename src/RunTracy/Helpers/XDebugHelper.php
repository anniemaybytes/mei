<?php

namespace RunTracy\Helpers;

use Tracy\IBarPanel;

class XDebugHelper implements IBarPanel
{
    protected $ideKey;


    public function __construct($ideKey = 'RUNTRACY')
    {
        $this->ideKey = $ideKey;
    }

    public function getPanel()
    {
        return false;
    }

    public function getTab()
    {
        ob_start();
        require __DIR__ . '../../Templates/XDebugHelperTab.phtml';
        return ob_get_clean();
    }
}
