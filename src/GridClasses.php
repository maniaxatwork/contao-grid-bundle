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

final class GridClasses
{
    /**
     * @var string
     */
    private $rowClass;

    /**
     * @var array<int>
     */
    private $columns;

    /**
     * @var bool
     */
    private $columns_no_column;

    /**
     * @var array<string>
     */
    private $viewports;

    /**
     * @var bool
     */
    private $viewports_no_viewport;

    /**
     * @var array<string>
     */
    private $column_prefixes;

    /**
     * @var array<string>
     */
    private $options_prefixes;

    /**
     * @var array<string>
     */
    private $pulls;

    /**
     * @var array<string>
     */
    private $positioning;

    /**
     * @var array<string>
     */
    private $directions;

    /**
     * @var array<int>
     */
    private $options_columns;

    public function __construct(string $rowClass, array $columns, bool $columns_no_column, array $viewports, bool $viewports_no_viewport, array $column_prefixes, array|null $options_prefixes, array|null $pulls, array|null $positioning, array|null $directions, array $options_columns)
    {
        $this->rowClass = $rowClass;
        $this->columns = $columns;
        $this->columns_no_column = $columns_no_column;
        $this->viewports = $viewports;
        $this->viewports_no_viewport = $viewports_no_viewport;
        $this->column_prefixes = $column_prefixes;
        $this->options_prefixes = $options_prefixes;
        $this->pulls = $pulls;
        $this->positioning = $positioning;
        $this->directions = $directions;
        $this->options_columns = $options_columns;
    }

    public function getRowClass(): string
    {
        return $this->rowClass;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function isColumnsNoColumn(): bool
    {
        return $this->columns_no_column;
    }

    public function getViewports(): array
    {
        return $this->viewports;
    }

    public function isViewportsNoViewport(): bool
    {
        return $this->viewports_no_viewport;
    }

    public function getColumnPrefixes(): array
    {
        return $this->column_prefixes;
    }

    public function getOptionsPrefixes(): array|null
    {
        return $this->options_prefixes;
    }

    public function getPulls(): array|null
    {
        return $this->pulls;
    }

    public function getPositioning(): array|null
    {
        return $this->positioning;
    }

    public function getDirections(): array|null
    {
        return $this->directions;
    }

    public function getOptionsColumns(): array
    {
        return $this->options_columns;
    }

    public function getGridColumnOptions(): array
    {
        $options = [];

        foreach ($this->getColumnPrefixes() as $option) {
            if ($this->isColumnsNoColumn()) {
                $options[$option][] = $option;
            }

            if ($this->isViewportsNoViewport()) {
                foreach ($this->getColumns() as $column) {
                    $options[$option][] = implode('-', [$option, $column]);
                }
            }

            foreach ($this->getViewports() as $viewport) {
                foreach ($this->getColumns() as $column) {
                    $options[$option.'-'.$viewport][] = implode('-', [$option, $viewport, $column]);
                }
            }
        }

        return $options;
    }

    public function getGridClassOptions(): array
    {
        $options = [];

        foreach ((array) $this->getOptionsPrefixes() as $prefix) {
            if ($this->isViewportsNoViewport()) {
                foreach ($this->getColumns() as $direction) {
                    $options[$prefix][] = implode('-', [$prefix, $direction]);
                }
            }

            foreach ($this->getViewports() as $viewport) {
                foreach ($this->getOptionsColumns() as $direction) {
                    $options[$prefix.'-'.$viewport][] = implode('-', [$prefix, $viewport, $direction]);
                }
            }
        }

        foreach ((array) $this->getPositioning() as $position) {
            if ($this->isViewportsNoViewport()) {
                foreach ($this->getDirections() as $direction) {
                    $options[$position][] = implode('-', [$position, $direction]);
                }
            }

            foreach ($this->getViewports() as $viewport) {
                foreach ((array) $this->getDirections() as $direction) {
                    $options[$position.'-'.$viewport][] = implode('-', [$position, $viewport, $direction]);
                }
            }
        }

        foreach ((array) $this->getPulls() as $pull) {
            foreach ($this->getViewports() as $viewport) {
                $options[$pull][] = implode('-', [$pull, $viewport]);
            }
        }

        return $options;
    }
}
