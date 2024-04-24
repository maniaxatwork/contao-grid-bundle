<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

use ManiaxAtWork\ContaoGridBundle\Driver\GridDriver;

$GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'] = GridDriver::class;

$GLOBALS['TL_DCA']['tl_content']['palettes']['rowStart'] = '{type_legend},type;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_content']['palettes']['rowEnd'] = '{type_legend},type';
$GLOBALS['TL_DCA']['tl_content']['palettes']['colStart'] =
    '{type_legend},type;{grid_legend},grid_columns,grid_options;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_content']['palettes']['colEnd'] = '{type_legend},type';

$GLOBALS['TL_DCA']['tl_content']['fields']['grid_columns'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_columns'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'select',
    'eval' => [
        'mandatory' => true,
        'multiple' => true,
        'size' => 10,
        'tl_class' => 'w50 w50h autoheight',
        'chosen' => true,
    ],
    'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['grid_options'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_options'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'select',
    'eval' => [
        'mandatory' => false,
        'multiple' => true,
        'size' => 10,
        'tl_class' => 'w50 w50h autoheight',
        'chosen' => true,
    ],
    'sql' => 'text NULL',
];
