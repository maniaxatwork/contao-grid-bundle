<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

namespace ManiaxAtWork\ContaoGridBundle;

use ManiaxAtWork\ContaoGridBundle\DependencyInjection\ManiaxAtWorkContaoGridExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ManiaxAtWorkContaoGridBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ManiaxAtWorkContaoGridExtension
    {
        return new ManiaxAtWorkContaoGridExtension();
    }
}
