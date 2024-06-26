<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

namespace ManiaxAtWork\ContaoGridBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;

final class RegisterFieldsInPaletteListener
{
    /**
     * @Callback(table="tl_content", target="config.onload", priority=-10)
     */
    public function onLoadContentCallback(): void
    {
        foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $k => $palette) {
            if (!\is_array($palette) && str_contains($palette, 'cssID') && str_contains($k, 'colStart')) {
                $GLOBALS['TL_DCA']['tl_content']['palettes'][$k] = str_replace(
                    '{invisible_legend',
                    '{grid_legend},grid_columns,grid_options;{invisible_legend',
                    $GLOBALS['TL_DCA']['tl_content']['palettes'][$k],
                );
            }
        }
    }
}
