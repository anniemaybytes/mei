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
    protected string $trigger;

    /**
     * XDebugHelper constructor.
     *
     * @param string $value value of xdebug.trigger_value
     */
    public function __construct(string $value)
    {
        $this->trigger = $value;
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
