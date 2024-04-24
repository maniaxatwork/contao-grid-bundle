<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

namespace ManiaxAtWork\ContaoGridBundle\Driver;

use Contao\ArrayUtil;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\Database;
use Contao\Date;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;

class GridDriver extends DC_Table
{
    /**
     * Name of the parent table.
     *
     * @var string
     */
    protected $ptable;

    /**
     * Names of the child tables.
     *
     * @var array
     */
    protected $ctable;

    /**
     * Limit (database query).
     *
     * @var string
     */
    protected $limit;

    /**
     * Total (database query).
     *
     * @var string
     */
    protected $total;

    /**
     * First sorting field.
     *
     * @var string
     */
    protected $firstOrderBy;

    /**
     * Order by (database query).
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Fields of a new or duplicated record.
     *
     * @var array
     */
    protected $set = [];

    /**
     * IDs of all records that are currently displayed.
     *
     * @var array
     */
    protected $current = [];

    /**
     * Show the current table as tree.
     *
     * @var bool
     */
    protected $treeView = false;

    /**
     * The current back end module.
     *
     * @var array
     */
    protected $arrModule = [];

    /**
     * Data of fields to be submitted.
     *
     * @var array
     */
    protected $arrSubmit = [];

    /**
     * Initialize the object.
     *
     * @param string $strTable
     * @param array  $arrModule
     */
    public function __construct($strTable, $arrModule = [])
    {
        parent::__construct($strTable);
    }

    /**
     * Show header of the parent table and list all records of the current table.
     *
     * @return string
     */
    protected function parentView()
    {
        $objSession = System::getContainer()->get('request_stack')->getSession();

        $blnClipboard = false;
        $arrClipboard = $objSession->get('CLIPBOARD');
        $table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) === self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;
        $blnHasSorting = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'][0] ?? null) === 'sorting';
        $blnMultiboard = false;

        // Check clipboard
        if (!empty($arrClipboard[$table])) {
            $blnClipboard = true;
            $arrClipboard = $arrClipboard[$table];

            if (\is_array($arrClipboard['id'] ?? null)) {
                $blnMultiboard = true;
            }
        } else {
            $arrClipboard = null;
        }

        // Load the language file and data container array of the parent table
        System::loadLanguageFile($this->ptable);
        $this->loadDataContainer($this->ptable);

        // Check the default labels (see #509)
        $labelNew = $GLOBALS['TL_LANG'][$this->strTable]['new'] ?? $GLOBALS['TL_LANG']['DCA']['new'];
        $labelCut = $GLOBALS['TL_LANG'][$this->strTable]['cut'] ?? $GLOBALS['TL_LANG']['DCA']['cut'];
        $labelPasteNew = $GLOBALS['TL_LANG'][$this->strTable]['pastenew'] ?? $GLOBALS['TL_LANG']['DCA']['pastenew'];
        $labelPasteAfter = $GLOBALS['TL_LANG'][$this->strTable]['pasteafter'] ?? $GLOBALS['TL_LANG']['DCA']['pasteafter'];
        $labelEditHeader = $GLOBALS['TL_LANG'][$this->ptable]['edit'] ?? $GLOBALS['TL_LANG']['DCA']['edit'];
        $limitHeight = BackendUser::getInstance()->doNotCollapse ? false : (int) ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['limitHeight'] ?? 0);

        $db = Database::getInstance();
        $security = System::getContainer()->get('security.helper');

        $buttons = (Input::get('nb') ? '' : ($this->ptable ? '
<a href="'.$this->getReferer(true, $this->ptable).'" class="header_back" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>' : (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']) ? '
<a href="'.System::getContainer()->get('router')->generate('contao_backend').'?'.$GLOBALS['TL_DCA'][$this->strTable]['config']['backlink'].'" class="header_back" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" data-action="contao--scroll-offset#discard">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>' : ''))).' '.('select' !== Input::get('act') && !$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX.$this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(['pid' => $this->intCurrentPid]))) ? '
<a href="'.$this->addToUrl($blnHasSorting ? 'act=paste&amp;mode=create' : 'act=create&amp;mode=2&amp;pid='.$this->intId).'" class="header_new" title="'.StringUtil::specialchars($labelNew[1]).'" accesskey="n" data-action="contao--scroll-offset#store">'.$labelNew[0].'</a> ' : '').($blnClipboard ? '
<a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ' : $this->generateGlobalButtons());

        $return = Message::generate().($buttons ? '<div id="tl_buttons">'.$buttons.'</div>' : '');

        // Get all details of the parent record
        $objParent = $db
            ->prepare('SELECT * FROM '.$this->ptable.' WHERE id=?')
            ->limit(1)
            ->execute($this->intCurrentPid)
        ;

        if ($objParent->numRows < 1) {
            return $return;
        }

        $return .= ('select' === Input::get('act') ? '

<form id="tl_select" class="tl_form'.('select' === Input::get('act') ? ' unselectable' : '').'" method="post" novalidate>
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="'.htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()).'">' : '').($blnClipboard ? '
<div id="paste_hint">
  <p>'.$GLOBALS['TL_LANG']['MSC']['selectNewPosition'].'</p>
</div>' : '').'
<div class="tl_listing_container parent_view'.($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['renderAsGrid'] ?? false ? ' as-grid' : '').($this->strPickerFieldType ? ' picker unselectable' : '').'" id="tl_listing"'.$this->getPickerValueAttribute().'>
<div class="tl_header click2edit toggle_select hover-div">';

        // List all records of the child table
        if (\in_array(Input::get('act'), ['paste', 'select', null], true)) {
            // Header
            $imagePasteNew = Image::getHtml('new.svg', $labelPasteNew[0]);
            $imagePasteAfter = Image::getHtml('pasteafter.svg', $labelPasteAfter[0]);
            $imageEditHeader = Image::getHtml('edit.svg', sprintf(\is_array($labelEditHeader) ? $labelEditHeader[0] : $labelEditHeader, $objParent->id));

            $security = System::getContainer()->get('security.helper');

            $return .= '
<div class="tl_content_right">'.('select' === Input::get('act') || 'checkbox' === $this->strPickerFieldType ? '
<label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">' : ($blnClipboard ? '
<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$objParent->id.(!$blnMultiboard ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars($labelPasteAfter[0]).'" data-action="contao--scroll-offset#store">'.$imagePasteAfter.'</a>' : (!($GLOBALS['TL_DCA'][$this->ptable]['config']['notEditable'] ?? null) && $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, $this->ptable) && $security->isGranted(ContaoCorePermissions::DC_PREFIX.$this->ptable, new UpdateAction($this->ptable, $objParent->row())) ? '
<a href="'.preg_replace('/&(amp;)?table=[^& ]*/i', $this->ptable ? '&amp;table='.$this->ptable : '', $this->addToUrl('act=edit'.(Input::get('nb') ? '&amp;nc=1' : ''))).'" class="edit" title="'.StringUtil::specialchars(sprintf(\is_array($labelEditHeader) ? $labelEditHeader[1] : $labelEditHeader, $objParent->id)).'">'.$imageEditHeader.'</a> '.$this->generateHeaderButtons($objParent->row(), $this->ptable) : '').($blnHasSorting && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX.$this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(['pid' => $objParent->id, 'sorting' => 0]))) ? '
<a href="'.$this->addToUrl('act=create&amp;mode=2&amp;pid='.$objParent->id.'&amp;id='.$this->intId).'" title="'.StringUtil::specialchars($labelPasteNew[0]).'">'.$imagePasteNew.'</a>' : ''))).'
</div>';

            // Format header fields
            $add = [];
            $headerFields = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['headerFields'];

            foreach ($headerFields as $v) {
                $_v = StringUtil::deserialize($objParent->$v);

                // Translate UUIDs to paths
                if (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['inputType'] ?? null) === 'fileTree') {
                    $objFiles = FilesModel::findMultipleByUuids((array) $_v);

                    if (null !== $objFiles) {
                        $_v = $objFiles->fetchEach('path');
                    }
                }

                if (\is_array($_v)) {
                    $_v = implode(', ', $_v);
                } elseif (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['inputType'] ?? null) === 'checkbox' && !($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['multiple'] ?? null))) {
                    $_v = $_v ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
                } elseif (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] ?? null) === 'date') {
                    $_v = $_v ? Date::parse(Config::get('dateFormat'), $_v) : '-';
                } elseif (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] ?? null) === 'time') {
                    $_v = $_v ? Date::parse(Config::get('timeFormat'), $_v) : '-';
                } elseif (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] ?? null) === 'datim') {
                    $_v = $_v ? Date::parse(Config::get('datimFormat'), $_v) : '-';
                } elseif ('tstamp' === $v) {
                    $_v = Date::parse(Config::get('datimFormat'), $objParent->tstamp);
                } elseif (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['foreignKey'])) {
                    $arrForeignKey = explode('.', $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['foreignKey'], 2);

                    $objLabel = $db
                        ->prepare('SELECT '.Database::quoteIdentifier($arrForeignKey[1]).' AS value FROM '.$arrForeignKey[0].' WHERE id=?')
                        ->limit(1)
                        ->execute($_v)
                    ;

                    $_v = $objLabel->numRows ? $objLabel->value : '-';
                } elseif (\is_array($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v] ?? null)) {
                    $_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v][0];
                } elseif (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v])) {
                    $_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v];
                } elseif (($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options'] ?? null)) {
                    $_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options'][$_v] ?? null;
                } elseif (\is_array($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options_callback'] ?? null)) {
                    $strClass = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options_callback'][0];
                    $strMethod = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options_callback'][1];

                    $options_callback = System::importStatic($strClass)->$strMethod($this);

                    $_v = $options_callback[$_v] ?? '-';
                } elseif (\is_callable($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options_callback'] ?? null)) {
                    $options_callback = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options_callback']($this);

                    $_v = $options_callback[$_v] ?? '-';
                }

                // Add the sorting field
                if ($_v) {
                    if (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label'])) {
                        $key = \is_array($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label']) ? $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label'][0] : $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label'];
                    } else {
                        $key = $GLOBALS['TL_LANG'][$this->ptable][$v][0] ?? $v;
                    }

                    $add[$key] = $_v;
                }
            }

            // Trigger the header_callback (see #3417)
            if (\is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] ?? null)) {
                $add = System::importStatic($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][0])->{$GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][1]}($add, $this);
            } elseif (\is_callable($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] ?? null)) {
                $add = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback']($add, $this);
            }

            // Output the header data
            $return .= '

<table class="tl_header_table">';

            foreach ($add as $k => $v) {
                if (\is_array($v)) {
                    $v = $v[0];
                }

                $return .= '
  <tr>
    <td><span class="tl_label">'.$k.':</span> </td>
    <td>'.$v.'</td>
  </tr>';
            }

            $return .= '
</table>
</div>';

            $orderBy = [];
            $firstOrderBy = [];

            // Add all records of the current table
            $query = 'SELECT * FROM '.$this->strTable;

            if (\is_array($this->orderBy) && isset($this->orderBy[0])) {
                $orderBy = $this->orderBy;
                $firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

                // Order by the foreign key
                if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey'])) {
                    $key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey'], 2);
                    $orderBy[0] = '(SELECT '.Database::quoteIdentifier($key[1]).' FROM '.$key[0].' WHERE '.$this->strTable.'.'.Database::quoteIdentifier($firstOrderBy).'='.$key[0].'.id)';
                }
            } elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] ?? null)) {
                $orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
                $firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);
            }

            $arrProcedure = $this->procedure;
            $arrValues = $this->values;

            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null) {
                $arrProcedure[] = 'ptable=?';
                $arrValues[] = $this->ptable;
            }

            // WHERE
            if (!empty($arrProcedure)) {
                $query .= ' WHERE '.implode(' AND ', $arrProcedure);
            }

            if (!empty($this->root) && \is_array($this->root)) {
                $query .= (!empty($arrProcedure) ? ' AND ' : ' WHERE ').'id IN('.implode(',', array_map('\intval', $this->root)).')';
            }

            // ORDER BY
            if (!empty($orderBy) && \is_array($orderBy)) {
                foreach ($orderBy as $k => $v) {
                    if (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'] ?? null, [self::SORT_INITIAL_LETTER_DESC, self::SORT_INITIAL_LETTERS_DESC, self::SORT_DAY_DESC, self::SORT_MONTH_DESC, self::SORT_YEAR_DESC, self::SORT_DESC], true)) {
                        $orderBy[$k] .= ' DESC';
                    }
                }

                $query .= ' ORDER BY '.implode(', ', $orderBy).', id';
            }

            $objOrderByStmt = $db->prepare($query);

            // LIMIT
            if ($this->limit) {
                $arrLimit = explode(',', $this->limit) + [null, null];
                $objOrderByStmt->limit($arrLimit[1], $arrLimit[0]);
            }

            $objOrderBy = $objOrderByStmt->execute(...$arrValues);

            if ($objOrderBy->numRows < 1) {
                return $return.'
<p class="tl_empty_parent_view">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>
</div>';
            }

            // Render the child records
            $strGroup = '';
            $blnIndent = false;
            $intWrapLevel = 0;
            $row = $objOrderBy->fetchAllAssoc();

            // Make items sortable
            if ($blnHasSorting) {
                $return .= '

<ul id="ul_'.$this->intCurrentPid.'">';
            }

            $beViewports = '
                    <div id="viewport_panel">
                        <ul class="buttons">
                            <li><span>Viewports</span></li>
                            <li title="XS" class="xs btn" data-viewport="xs"><span>XS</span></li>
                            <li title="SM" class="sm btn" data-viewport="sm"><span>SM</span></li>
                            <li title="MD" class="md btn" data-viewport="md"><span>MD</span></li>
                            <li title="LG" class="lg btn" data-viewport="lg"><span>LG</span></li>
                            <li title="XL" class="xl btn active" data-viewport="xl"><span>XL</span></li>
                        </ul>
                    </div>';

            for ($i = 0, $c = \count($row); $i < $c; ++$i) {
                // Improve performance
                static::setCurrentRecordCache($row[$i]['id'], $this->strTable, $row[$i]);

                $this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX.$this->strTable, new ReadAction($this->strTable, $row[$i]));

                $this->current[] = $row[$i]['id'];
                $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($labelPasteAfter[1] ?? $labelPasteAfter[0], $row[$i]['id']));
                $imagePasteNew = Image::getHtml('new.svg', sprintf($labelPasteNew[1] ?? $labelPasteNew[0], $row[$i]['id']));

                // Make items sortable
                if ($blnHasSorting) {
                    $colClose = false;
                    $rowClose = false;
                    $customClasses = '';

                    if ('rowStart' === $row[$i]['type']) {
                        $return .= $beViewports.'<li class="grid" data-viewport="xl"><ul class="row">';
                        $beViewports = '';
                        $customClasses .= 'rowStart';
                    } elseif ('rowEnd' === $row[$i]['type']) {
                        $customClasses .= 'rowEnd';
                        $rowClose = true;
                    } elseif ('colStart' === $row[$i]['type']) {
                        $colClasses = '';
                        $defaultCol = 'col-xl-12';
                        $arrColClasses = StringUtil::deserialize($row[$i]['grid_columns']);
                        if (null !== $arrColClasses) {
                            $colClasses .= implode(' ', $arrColClasses);

                            foreach ($arrColClasses as $classes) {
                                $pos = strpos($classes, 'xl');
                                if (false !== $pos) {
                                    $defaultCol = $classes;
                                    break;
                                }
                            }
                        }
                        $return .= '<li class="'.$colClasses.'" data-cols="'.$defaultCol.'"><ul class="colStart ">';
                    } elseif ('colEnd' === $row[$i]['type']) {
                        $customClasses .= 'colEnd';
                        $colClose = true;
                    }
                    $return .= '
                        <li id="li_'.$row[$i]['id'].'" class="'.$customClasses.'">';
                }

                // Add the group header
                if ('sorting' !== $firstOrderBy && !($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['disableGrouping'] ?? null)) {
                    $sortingMode = 1 === \count($orderBy) && $firstOrderBy === $orderBy[0] && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null);
                    $remoteNew = $this->formatCurrentValue($firstOrderBy, $row[$i][$firstOrderBy], $sortingMode);
                    $group = $this->formatGroupHeader($firstOrderBy, $remoteNew, $sortingMode, $row[$i]);

                    if ($group !== $strGroup) {
                        $return .= "\n\n".'<div class="tl_content_header">'.$group.'</div>';
                        $strGroup = $group;
                    }
                }

                $blnWrapperStart = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['start'], true);
                $blnWrapperSeparator = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['separator'], true);
                $blnWrapperStop = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['stop'], true);
                $blnIndentFirst = isset($row[$i - 1]['type']) && \in_array($row[$i - 1]['type'], $GLOBALS['TL_WRAPPERS']['start'], true);
                $blnIndentLast = isset($row[$i + 1]['type']) && \in_array($row[$i + 1]['type'], $GLOBALS['TL_WRAPPERS']['stop'], true);

                // Closing wrappers
                if ($blnWrapperStop && --$intWrapLevel < 1) {
                    $blnIndent = false;
                }
                $strGridButtons = '';
                if ('colStart' === $row[$i]['type']) {
                    $strGridButtons = '
<div class="grid-buttons"><span class="plus btn" data-action="plus">+</span> <span class="minus btn" data-action="minus">-</span></div>';
                }
                $return .= '
<div class="tl_content'.($blnWrapperStart ? ' wrapper_start' : '').($blnWrapperSeparator ? ' wrapper_separator' : '').($blnWrapperStop ? ' wrapper_stop' : '').($blnIndent ? ' indent indent_'.$intWrapLevel : '').($blnIndentFirst ? ' indent_first' : '').($blnIndentLast ? ' indent_last' : '').('0' === (string) $row[$i]['tstamp'] ? ' draft' : '').(!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_class']) ? ' '.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_class'] : '').' click2edit toggle_select">
<div class="inside hover-div"'.($limitHeight && !$blnWrapperStart && !$blnWrapperStop && !$blnWrapperSeparator ? ' data-contao--limit-height-target="node"' : '').'>
'.$strGridButtons.'<div class="tl_content_right">';

                // Opening wrappers
                if ($blnWrapperStart && ++$intWrapLevel > 0) {
                    $blnIndent = true;
                }

                // Edit multiple
                if ('select' === Input::get('act')) {
                    $return .= '<input type="checkbox" name="IDS[]" id="ids_'.$row[$i]['id'].'" class="tl_tree_checkbox" value="'.$row[$i]['id'].'">';
                }

                // Regular buttons
                else {
                    $return .= $this->generateButtons($row[$i], $this->strTable, $this->root, false, null, $row[$i - 1]['id'] ?? null, $row[$i + 1]['id'] ?? null);

                    // Sortable table
                    if ($blnHasSorting) {
                        // Create new button
                        if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX.$this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(['pid' => $row[$i]['pid'], 'sorting' => $row[$i]['sorting'] + 1])))) {
                            $return .= ' <a href="'.$this->addToUrl('act=create&amp;mode=1&amp;pid='.$row[$i]['id'].'&amp;id='.$objParent->id.(Input::get('nb') ? '&amp;nc=1' : '')).'" title="'.StringUtil::specialchars(sprintf($labelPasteNew[1], $row[$i]['id'])).'">'.$imagePasteNew.'</a>';
                        }

                        // Prevent circular references
                        if (($blnClipboard && 'cut' === $arrClipboard['mode'] && $row[$i]['id'] === $arrClipboard['id']) || ($blnMultiboard && 'cutAll' === $arrClipboard['mode'] && \in_array($row[$i]['id'], $arrClipboard['id'], true))) {
                            $return .= ' '.Image::getHtml('pasteafter--disabled.svg');
                        }

                        // Copy/move multiple
                        elseif ($blnMultiboard) {
                            $return .= ' <a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row[$i]['id']).'" title="'.StringUtil::specialchars(sprintf($labelPasteAfter[1], $row[$i]['id'])).'" data-action="contao--scroll-offset#store">'.$imagePasteAfter.'</a>';
                        }

                        // Paste buttons
                        elseif ($blnClipboard) {
                            $return .= ' <a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row[$i]['id'].'&amp;id='.$arrClipboard['id']).'" title="'.StringUtil::specialchars(sprintf($labelPasteAfter[1], $row[$i]['id'])).'" data-action="contao--scroll-offset#store">'.$imagePasteAfter.'</a>';
                        }

                        // Drag handle
                        if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX.$this->strTable, new UpdateAction($this->strTable, $row[$i]))) {
                            $return .= ' <button type="button" class="drag-handle" title="'.StringUtil::specialchars(sprintf(\is_array($labelCut) ? $labelCut[1] : $labelCut, $row[$i]['id'])).'" aria-hidden="true">'.Image::getHtml('drag.svg').'</button>';
                        }
                    }

                    // Picker
                    if ($this->strPickerFieldType) {
                        $return .= $this->getPickerInputField($row[$i]['id']);
                    }
                }

                if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'] ?? null)) {
                    $strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][0];
                    $strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][1];

                    $return .= '</div>'.System::importStatic($strClass)->$strMethod($row[$i]).'</div>';
                } elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'] ?? null)) {
                    $return .= '</div>'.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']($row[$i]).'</div>';
                } else {
                    $return .= '</div><div class="tl_content_left">'.$this->generateRecordLabel($row[$i]).'</div></div>';
                }

                $return .= '</div>';

                // Make items sortable
                if ($blnHasSorting) {
                    $return .= '</li>';

                    if ($rowClose || $colClose) {
                        $return .= '</ul></li>';

                        if ($rowClose) {
                            $rowClose = false;
                        }
                        if ($colClose) {
                            $rowClose = false;
                        }
                    }
                }
            }
        }

        // Make items sortable
        if ($blnHasSorting) {
            $return .= '
</ul>';

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null) && 'select' !== Input::get('act')) {
                $return .= '
<script>
  Backend.makeParentViewSortable("ul_'.$this->intCurrentPid.'");
</script>';
            }
        }

        $return .= ('radio' === $this->strPickerFieldType ? '
<div class="tl_radio_reset">
<label for="tl_radio_reset" class="tl_radio_label">'.$GLOBALS['TL_LANG']['MSC']['resetSelected'].'</label> <input type="radio" name="picker" id="tl_radio_reset" value="" class="tl_tree_radio">
</div>' : '').'
</div>';

        // Add another panel at the end of the page
        if (str_contains($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] ?? '', 'limit')) {
            $return .= $this->paginationMenu();
        }

        // Close the form
        if ('select' === Input::get('act')) {
            // Submit buttons
            $arrButtons = [];

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)) {
                $arrButtons['edit'] = '<button type="submit" name="edit" id="edit" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG']['MSC']['editSelected'].'</button>';
            }

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ?? null)) {
                $arrButtons['delete'] = '<button type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')">'.$GLOBALS['TL_LANG']['MSC']['deleteSelected'].'</button>';
            }

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null)) {
                $arrButtons['copy'] = '<button type="submit" name="copy" id="copy" class="tl_submit" accesskey="c">'.$GLOBALS['TL_LANG']['MSC']['copySelected'].'</button>';
                $arrButtons['copyMultiple'] = '<button type="submit" name="copyMultiple" id="copyMultiple" class="tl_submit" accesskey="m">'.$GLOBALS['TL_LANG']['MSC']['copyMultiple'].'</button>';
            }

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null)) {
                $arrButtons['cut'] = '<button type="submit" name="cut" id="cut" class="tl_submit" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['moveSelected'].'</button>';
            }

            if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)) {
                $arrButtons['override'] = '<button type="submit" name="override" id="override" class="tl_submit" accesskey="v">'.$GLOBALS['TL_LANG']['MSC']['overrideSelected'].'</button>';
            }

            // Call the buttons_callback (see #4691)
            if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] ?? null)) {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback) {
                    if (\is_array($callback)) {
                        $arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
                    } elseif (\is_callable($callback)) {
                        $arrButtons = $callback($arrButtons, $this);
                    }
                }
            }

            if (\count($arrButtons) < 3) {
                $strButtons = implode(' ', $arrButtons);
            } else {
                $strButtons = array_shift($arrButtons).' ';
                $strButtons .= '<div class="split-button">';
                $strButtons .= array_shift($arrButtons).'<button type="button" id="sbtog">'.Image::getHtml('navcol.svg').'</button> <ul class="invisible">';

                foreach ($arrButtons as $strButton) {
                    $strButtons .= '<li>'.$strButton.'</li>';
                }

                $strButtons .= '</ul></div>';
            }

            $return .= '
</div>
<div class="tl_formbody_submit" style="text-align:right">
<div class="tl_submit_container">
  '.$strButtons.'
</div>
</div>
</form>';
        }

        if ($limitHeight) {
            $return = '<div
				data-controller="contao--limit-height"
				data-contao--limit-height-max-value="'.$limitHeight.'"
				data-contao--limit-height-expand-value="'.$GLOBALS['TL_LANG']['MSC']['expandNode'].'"
				data-contao--limit-height-collapse-value="'.$GLOBALS['TL_LANG']['MSC']['collapseNode'].'"
				data-contao--limit-height-expand-all-value="'.$GLOBALS['TL_LANG']['DCA']['expandNodes'][0].'"
				data-contao--limit-height-expand-all-title-value="'.$GLOBALS['TL_LANG']['DCA']['expandNodes'][1].'"
				data-contao--limit-height-collapse-all-value="'.$GLOBALS['TL_LANG']['DCA']['collapseNodes'][0].'"
				data-contao--limit-height-collapse-all-title-value="'.$GLOBALS['TL_LANG']['DCA']['collapseNodes'][0].'"
			>'.$return.'</div>';
        }

        return $return;
    }

    private function addDynamicPtable(array $data): array
    {
        if (($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? false) && !isset($data['ptable'])) {
            $data['ptable'] = $this->ptable;
        }

        return $data;
    }
}
