<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;

/**
 * @Callback(table="tl_content", target="config.onload")
 */
class ContentOnLoadCallbackListener
{
    public function __invoke(): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/maniaxatworkcontaogrid/maw_be_grid.css';
        $GLOBALS['TL_CSS'][] = 'bundles/maniaxatworkcontaogrid/maw_grid_backend.css';
        $GLOBALS['TL_MOOTOOLS'][] = '<script src="bundles/maniaxatworkcontaogrid/be_maw_grid.js"></script>';
    }
}
