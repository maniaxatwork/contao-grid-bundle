<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

$GLOBALS['TL_WRAPPERS']['start'][] = 'rowStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'rowEnd';
$GLOBALS['TL_WRAPPERS']['start'][] = 'colStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'colEnd';
