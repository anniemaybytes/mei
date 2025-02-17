<?php

declare(strict_types=1);

namespace Mei\PDO;

use SqlFormatter;
use Tracy\IBarPanel;

/**
 * Class PDOTracyBarPanel
 *
 * @package Mei\PDO
 */
final class PDOTracyBarPanel implements IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     *
     * @var string
     * @see http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected string $icon = 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg' .
    'o8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTYuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCd' .
    'WlsZCAwKSAgLS0+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBo' .
    'aWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR' .
    '0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgd2lkdGg9IjE2cHgiIG' .
    'hlaWdodD0iMTZweCIgdmlld0JveD0iMCAwIDk1LjEwMyA5NS4xMDMiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDk1LjEwMyA5N' .
    'S4xMDM7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPGc+Cgk8ZyBpZD0iTGF5ZXJfMV8xNF8iPgoJCTxnPgoJCQk8Zz4KCQkJCTxnPgoJCQkJCTxw' .
    'YXRoIGQ9Ik00Ny41NjEsMEMyNS45MjgsMCw4LjM5LDYuMzkzLDguMzksMTQuMjgzdjExLjcyYzAsNy44OTEsMTcuNTM4LDE0LjI4MiwzOS4xNzE' .
    'sMTQuMjgyICAgICAgIGMyMS42MzIsMCwzOS4xNy02LjM5MiwzOS4xNy0xNC4yODJ2LTExLjcyQzg2LjczMSw2LjM5Myw2OS4xOTMsMCw0Ny41Nj' .
    'EsMHoiIGZpbGw9IiMyYjJiMmIiLz4KCQkJCTwvZz4KCQkJPC9nPgoJCQk8Zz4KCQkJCTxnPgoJCQkJCTxwYXRoIGQ9Ik00Ny41NjEsNDcuMTE1Y' .
    'y0yMC42NTQsMC0zNy42ODItNS44MzItMzkuMTcxLTEzLjIyN2MtMC4wNzEsMC4zNTMsMCwxOS4zNTUsMCwxOS4zNTUgICAgICAgYzAsNy44OTIs' .
    'MTcuNTM4LDE0LjI4MywzOS4xNzEsMTQuMjgzYzIxLjYzMiwwLDM5LjE3LTYuMzkzLDM5LjE3LTE0LjI4M2MwLDAsMC4wNDQtMTkuMDAzLTAuMDI' .
    '2LTE5LjM1NSAgICAgICBDODUuMjE0LDQxLjI4NCw2OC4yMTQsNDcuMTE1LDQ3LjU2MSw0Ny4xMTV6IiBmaWxsPSIjMmIyYjJiIi8+CgkJCQk8L2' .
    'c+CgkJCTwvZz4KCQkJPHBhdGggZD0iTTg2LjY5NCw2MS40NjRjLTEuNDg4LDcuMzkxLTE4LjQ3OSwxMy4yMjYtMzkuMTMzLDEzLjIyNlM5Ljg3N' .
    'Sw2OC44NTQsOC4zODYsNjEuNDY0TDguMzksODAuODIgICAgIGMwLDcuODkxLDE3LjUzOCwxNC4yODIsMzkuMTcxLDE0LjI4MmMyMS42MzIsMCwz' .
    'OS4xNy02LjM5MywzOS4xNy0xNC4yODJMODYuNjk0LDYxLjQ2NHoiIGZpbGw9IiMyYjJiMmIiLz4KCQk8L2c+Cgk8L2c+CjwvZz4KPGc+CjwvZz4' .
    'KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPG' .
    'c+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPC9zdmc+Cg==';

    private PDOLogger $logger;

    public function __construct(PDOLogger $logger)
    {
        $this->logger = $logger;
    }

    public function getTab(): string
    {
        $html = "<img src=\"$this->icon\" alt=\"" . $this->logger->getProvider() . ' queries" /> ';
        switch ($count = $this->logger->getEventsCount()) {
            case 0:
                $html .= 'no queries';
                return $html;
            case 1:
                $html .= '1 query';
                break;
            default:
                $html .= "$count queries";
                break;
        }

        return $html . ' / ' . number_format($this->logger->getExecutionTime(), 2, '.', ' ') . ' ms';
    }

    public function getPanel(): string
    {
        SqlFormatter::$pre_attributes = 'style="color: black;"';

        $html = '<h1 style="font-size:1.6em">' . $this->logger->getProvider() .
            '</h1><div class="tracy-inner tracy-InfoPanel">';
        if ($this->logger->getEventsCount() > 0) {
            $html .= '<table><tr><th>Time</th><th>Statement</th></tr>';
            foreach ($this->logger->getEvents() as $query) {
                $html .= '<tr><td><span>' .
                    number_format($query['time'], 2, '.', '&nbsp;') .
                    '&nbsp;ms</span></td><td>' . SqlFormatter::highlight($query['statement']) . '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="font-size:1.2em;font-weight:bold;padding:10px">No queries were executed</p>';
        }

        return $html . '</div>';
    }
}
