<?php

declare(strict_types=1);

namespace RunTracy\Helpers;

use Tracy\IBarPanel;

/**
 * Class IncludedFiles
 *
 * @author 1f7.wizard@gmail.com
 * @package RunTracy\Helpers
 */
final class IncludedFiles implements IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     *
     * @var string
     */
    protected string $icon = '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16px" ' .
    'height="16px" viewBox="0 0 512 512" xml:space="preserve">' .
    '<g><path d="M506.195,307.084H5.805c-3.206,0-5.805,2.599-5.805,5.805V488.2c0,9.618,7.797,17.415,17.415,' .
    '17.415h477.17 c9.618,0,17.415-7.797,17.415-17.415V312.889C512,309.683,509.401,307.084,506.195,307.' .
    '084z M256,456.853 c-27.848,0-50.503-22.656-50.503-50.503c0-27.848,22.656-50.503,50.503-50.503s50.' .
    '503,22.656,50.503,50.503 C306.503,434.197,283.848,456.853,256,456.853z" fill="#006DF0"/></g><g>' .
    '<path d="M34.089,201.603L8.861,264.281c-1.536,3.815,1.272,7.973,5.385,7.973h25.229c3.206,0,5.805-2.' .
    '599,5.805-5.805V203.77 C45.279,197.386,36.473,195.68,34.089,201.603z" fill="#006DF0"/></g><g><path ' .
    'd="M503.261,264.308l-24.178-60.952c-2.36-5.95-11.201-4.261-11.201,2.141v60.952c0,3.206,2.599,5.805,' .
    '5.805,5.805h24.178 C501.964,272.254,504.773,268.12,503.261,264.308z" fill="#006DF0"/></g><g><path ' .
    'd="M415.637,6.386H97.524c-9.618,0-17.415,7.797-17.415,17.415v242.648c0,3.206,2.599,5.805,5.805,' .
    '5.805h341.333 c3.206,0,5.805-2.599,5.805-5.805V23.8C433.052,14.183,425.255,6.386,415.637,6.386z ' .
    'M218.268,89.977h76.626 c9.618,0,17.415,7.797,17.415,17.415c0,9.618-7.797,17.415-17.415,17.415h-' .
    '76.626c-9.618,0-17.415-7.797-17.415-17.415 C200.853,97.775,208.65,89.977,218.268,89.977z M341.333,' .
    '188.662H171.828c-9.618,0-17.415-7.797-17.415-17.415 c0-9.618,7.797-17.415,17.415-17.415h169.506c9.' .
    '618,0,17.415,7.797,17.415,17.415 C358.748,180.865,350.951,188.662,341.333,188.662z" ' .
    'fill="#006DF0"/></g></svg>';

    public function getTab(): string
    {
        return '<span title="Included Files">' . $this->icon . '</span>';
    }

    public function getPanel(): string
    {
        $files = get_included_files();
        $ret = '<thead><tr><th><b>Count</b></th><th>File</th></tr></thead>';

        $num = 0;
        foreach ($files as $num => $file) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td></tr>', ++$num, $file);
        }

        return '<h1>' . $this->icon . ' &nbsp; Included Files: ' . $num . '</h1>
            <div class="tracy-inner"><table style="width: 100%;">' . $ret . '</table></div>';
    }
}
