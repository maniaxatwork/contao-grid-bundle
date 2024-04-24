<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

namespace ManiaxAtWork\ContaoGridBundle\DependencyInjection;

use ManiaxAtWork\ContaoGridBundle\EventListener\DataContainer\TranslatedLabelsListener;
use ManiaxAtWork\ContaoGridBundle\GridClasses;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class ManiaxAtWorkContaoGridExtension extends Extension
{
    public function getAlias(): string
    {
        return 'maw_contao_grid';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yaml');

        $definition = $container->getDefinition(TranslatedLabelsListener::class);
        $definition->setArgument(0, $config['translated_labels']);

        $definition = $container->getDefinition(GridClasses::class);
        $definition->setArguments([
            $config['row_class'],
            $config['columns'],
            $config['columns_no_column'],
            $config['viewports'],
            $config['viewports_no_viewport'],
            $config['column_prefixes'],
            $config['options_prefixes'],
            $config['pulls'],
            $config['positioning'],
            $config['directions'],
            $config['options_columns'],
        ]);
    }
}
