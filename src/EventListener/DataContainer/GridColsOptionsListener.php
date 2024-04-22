<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use ManiaxAtWork\ContaoGridBundle\GridClasses;

/**
 * @Callback(table="tl_content", target="fields.grid_columns.options")
 */
final class GridColsOptionsListener
{

    public function __construct(private readonly GridClasses $gridClasses)
    {
    }

    public function __invoke(): array
    {
        return $this->gridClasses->getGridColumnOptions();
    }
}
