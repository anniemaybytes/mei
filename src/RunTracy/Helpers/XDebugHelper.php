<?php

declare(strict_types=1);

namespace RunTracy\Helpers;

use Tracy\IBarPanel;

/**
 * Class XDebugHelper
 *
 * @author 1f7.wizard@gmail.com
 * @package RunTracy\Helpers
 */
final class XDebugHelper implements IBarPanel
{
    protected string $triggerValue;

    /**
     * XDebugHelper constructor.
     *
     * @param string $triggerValue value of xdebug.trigger_value
     */
    public function __construct(string $triggerValue)
    {
        $this->triggerValue = $triggerValue;
    }

    public function getPanel(): string
    {
        return '';
    }

    public function getTab(): string
    {
        ob_start();
        require __DIR__ . '../../Templates/XDebugHelperTab.phtml';
        return ob_get_clean();
    }
}
