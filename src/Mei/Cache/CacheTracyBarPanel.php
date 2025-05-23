<?php

declare(strict_types=1);

namespace Mei\Cache;

use Tracy\Debugger;
use Tracy\IBarPanel;

/**
 * Class CacheTracyBarPanel
 *
 * @package Mei\Cache
 */
final class CacheTracyBarPanel implements IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     *
     * @var string
     * @see https://www.flaticon.com/free-icon/speedometer_1531130
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    public string $icon = 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3' .
    'cudzMub3JnLzIwMDAvc3ZnIiBoZWlnaHQ9IjE2cHgiIHZpZXdCb3g9IjEgMSA1MTEuOTk5OTggNTExLjk5OTk4IiB3aWR0aD0iMTZweCI+PHBhd' .
    'GggZD0ibTUxMiAyNTZjMCAxNDEuMzg2NzE5LTExNC42MTMyODEgMjU2LTI1NiAyNTZzLTI1Ni0xMTQuNjEzMjgxLTI1Ni0yNTYgMTE0LjYxMzI4' .
    'MS0yNTYgMjU2LTI1NiAyNTYgMTE0LjYxMzI4MSAyNTYgMjU2em0wIDAiIGZpbGw9IiMxODI4MmQiLz48cGF0aCBkPSJtNDExLjk4NDM3NSA0MTE' .
    'uOTgwNDY5LTE1NS45ODA0NjktMTU1Ljk4MDQ2OS0xNTUuOTkyMTg3IDE1NS45OTIxODhjLTg2LjE1MjM0NC04Ni4xNTIzNDQtODYuMTUyMzQ0LT' .
    'IyNS44MzU5MzggMC0zMTEuOTg0Mzc2IDg2LjE1MjM0My04Ni4xNTIzNDMgMjI1LjgzMjAzMS04Ni4xNTIzNDMgMzExLjk3MjY1NiAwIDg2LjE1M' .
    'jM0NCA4Ni4xNDg0MzggODYuMTUyMzQ0IDIyNS44MjAzMTMgMCAzMTEuOTcyNjU3em0wIDAiIGZpbGw9IiNmZjI3NTEiLz48cGF0aCBkPSJtNDEx' .
    'Ljk4NDM3NSAxMDAuMDE1NjI1YzQzLjA4MjAzMSA0My4wODIwMzEgNjQuNjE3MTg3IDk5LjUzOTA2MyA2NC42MTcxODcgMTU1Ljk4NDM3NWgtMjI' .
    'wLjU5NzY1NnptMCAwIiBmaWxsPSIjMjhiMjRiIi8+PHBhdGggZD0ibTI1Ni4wMDM5MDYgMjU2aDIyMC41OTc2NTZjMCA1Ni40NjQ4NDQtMjEuNT' .
    'Q2ODc0IDExMi44OTg0MzgtNjQuNjE3MTg3IDE1NS45ODA0Njl6bTAgMCIgZmlsbD0iIzM5ZDM2NSIvPjxwYXRoIGQ9Im00MTEuOTg0Mzc1IDEwM' .
    'C4wMDc4MTJ2LjAwNzgxM2wtMTU1Ljk4MDQ2OSAxNTUuOTg0Mzc1di0yMjAuNjA5Mzc1YzU2LjQ1MzEyNSAwIDExMi45MTAxNTYgMjEuNTQyOTY5' .
    'IDE1NS45ODA0NjkgNjQuNjE3MTg3em0wIDAiIGZpbGw9IiNmNGNhMTkiLz48cGF0aCBkPSJtMjU2LjAwMzkwNiAzNS4zOTA2MjV2MjIwLjYwOTM' .
    '3NWwtMTU1Ljk5MjE4Ny0xNTUuOTkyMTg4YzQzLjA3ODEyNS00My4wNzQyMTggOTkuNTM1MTU2LTY0LjYxNzE4NyAxNTUuOTkyMTg3LTY0LjYxNz' .
    'E4N3ptMCAwIiBmaWxsPSIjZTI5YjBlIi8+PHBhdGggZD0ibTI1Ni4wMDM5MDYgMjU2LTE1NS45OTIxODcgMTU1Ljk5MjE4OGMtNDMuMDgyMDMxL' .
    'TQzLjA3MDMxMy02NC42MTcxODgtOTkuNTM5MDYzLTY0LjYxNzE4OC0xNTUuOTkyMTg4em0wIDAiIGZpbGw9IiNkMTBmNDYiLz48cGF0aCBkPSJt' .
    'MzYwLjgwMDc4MSAyNTZjMCA1Ny44Nzg5MDYtNDYuOTIxODc1IDEwNC44MDA3ODEtMTA0LjgwMDc4MSAxMDQuODAwNzgxcy0xMDQuODAwNzgxLTQ' .
    '2LjkyMTg3NS0xMDQuODAwNzgxLTEwNC44MDA3ODEgNDYuOTIxODc1LTEwNC44MDA3ODEgMTA0LjgwMDc4MS0xMDQuODAwNzgxIDEwNC44MDA3OD' .
    'EgNDYuOTIxODc1IDEwNC44MDA3ODEgMTA0LjgwMDc4MXptMCAwIiBmaWxsPSIjMTgyODJkIi8+PHBhdGggZD0ibTQyNS4yNTM5MDYgMTYzLjE5N' .
    'TMxMi0xODEuNTQyOTY4IDc3LjQ5MjE4OCAxOC41ODk4NDMgMzMuOTAyMzQ0em0wIDAiIGZpbGw9IiNmZmYiLz48cGF0aCBkPSJtMjk1Ljk4ODI4' .
    'MSAyNTUuOTk2MDk0YzAgMjIuMDg1OTM3LTE3LjkwMjM0MyAzOS45ODgyODEtMzkuOTg4MjgxIDM5Ljk4ODI4MS0yMi4wODIwMzEgMC0zOS45ODQ' .
    'zNzUtMTcuOTAyMzQ0LTM5Ljk4NDM3NS0zOS45ODgyODEgMC0yMi4wODIwMzIgMTcuOTAyMzQ0LTM5Ljk4ODI4MiAzOS45ODQzNzUtMzkuOTg4Mj' .
    'gyIDIyLjA4NTkzOCAwIDM5Ljk4ODI4MSAxNy45MDYyNSAzOS45ODgyODEgMzkuOTg4Mjgyem0wIDAiIGZpbGw9IiNlNmU2ZTYiLz48ZyBmaWxsP' .
    'SIjMzE0NzRjIj48cGF0aCBkPSJtMzM3LjM1OTM3NSA0NjQuMjE0ODQ0aC0zMC4yNjU2MjVjLTUuNDc2NTYyIDAtOS45MTQwNjItNC40Mzc1LTku' .
    'OTE0MDYyLTkuOTEwMTU2di02NS44NTkzNzZjMC01LjQ3MjY1NiA0LjQzNzUtOS45MTAxNTYgOS45MTQwNjItOS45MTAxNTZoMzAuMjY1NjI1YzU' .
    'uNDcyNjU2IDAgOS45MTAxNTYgNC40Mzc1IDkuOTEwMTU2IDkuOTEwMTU2djY1Ljg1OTM3NmMwIDUuNDcyNjU2LTQuNDM3NSA5LjkxMDE1Ni05Lj' .
    'kxMDE1NiA5LjkxMDE1NnptMCAwIi8+PHBhdGggZD0ibTI3MS4xMzI4MTIgNDY0LjIxNDg0NGgtMzAuMjY1NjI0Yy01LjQ3NjU2MyAwLTkuOTE0M' .
    'DYzLTQuNDM3NS05LjkxNDA2My05LjkxMDE1NnYtNjUuODU5Mzc2YzAtNS40NzI2NTYgNC40Mzc1LTkuOTEwMTU2IDkuOTE0MDYzLTkuOTEwMTU2' .
    'aDMwLjI2NTYyNGM1LjQ3NjU2MyAwIDkuOTE0MDYzIDQuNDM3NSA5LjkxNDA2MyA5LjkxMDE1NnY2NS44NTkzNzZjMCA1LjQ3MjY1Ni00LjQzNzU' .
    'gOS45MTAxNTYtOS45MTQwNjMgOS45MTAxNTZ6bTAgMCIvPjxwYXRoIGQ9Im0yMDQuOTA2MjUgNDY0LjIxNDg0NGgtMzAuMjY1NjI1Yy01LjQ3Mj' .
    'Y1NiAwLTkuOTEwMTU2LTQuNDM3NS05LjkxMDE1Ni05LjkxMDE1NnYtNjUuODU5Mzc2YzAtNS40NzI2NTYgNC40Mzc1LTkuOTEwMTU2IDkuOTEwM' .
    'TU2LTkuOTEwMTU2aDMwLjI2NTYyNWM1LjQ3NjU2MiAwIDkuOTE0MDYyIDQuNDM3NSA5LjkxNDA2MiA5LjkxMDE1NnY2NS44NTkzNzZjMCA1LjQ3' .
    'MjY1Ni00LjQzNzUgOS45MTAxNTYtOS45MTQwNjIgOS45MTAxNTZ6bTAgMCIvPjwvZz48L3N2Zz4K';

    private IKeyStore $cache;
    private array $hits;

    private string $provider;

    public function __construct(IKeyStore $cache)
    {
        $this->cache = $cache;
        $this->hits = [];

        $this->provider = get_class($cache);
    }

    public function getTab(): string
    {
        if (!$this->hits) {
            $this->hits = [
                'hits' => $this->cache->getCacheHits(),
                'time' => $this->cache->getExecutionTime()
            ];
        }

        $html = '<img src="' . $this->icon . '" alt="' . $this->provider . ' hits" /> ';
        $hits = count($this->hits['hits']);
        if ($hits === 0) {
            $html .= 'no hits';
            return $html;
        }
        if ($hits === 1) {
            $html .= '1 hit';
        } else {
            $html .= $hits . ' hits';
        }

        return $html . ' / ' . number_format($this->hits['time'], 2, '.', ' ') . ' ms';
    }

    public function getPanel(): string
    {
        if (!$this->hits) {
            $this->hits = [
                'hits' => $this->cache->getCacheHits(),
                'time' => $this->cache->getExecutionTime()
            ];
        }

        $html = '<h1 style="font-size:1.6em">' . $this->provider . '</h1><div class="tracy-inner tracy-InfoPanel">';
        if (count($this->hits['hits']) > 0) {
            $html .= Debugger::dump($this->hits['hits'], true);
        } else {
            $html .= '<p style="font-size:1.2em;font-weight:bold;padding:10px">No cache hits</p>';
        }

        return $html . '</div>';
    }
}
