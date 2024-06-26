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
use ManiaxAtWork\ContaoGridBundle\GridClasses;

/**
 * @Callback(table="tl_content", target="fields.grid_options.options")
 */
final class GridClassesOptionsListener
{
    public function __construct(private readonly GridClasses $gridClasses)
    {
    }

    public function __invoke(): array
    {
        return $this->gridClasses->getGridClassOptions();
    }
}
