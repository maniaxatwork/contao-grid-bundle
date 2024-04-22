<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use ManiaxAtWork\ContaoGridBundle\ManiaxAtWorkContaoGridBundle;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{

    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ManiaxAtWorkContaoGridBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
