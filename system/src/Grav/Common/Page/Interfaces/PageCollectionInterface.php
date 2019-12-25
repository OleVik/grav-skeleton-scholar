<?php

/**
 * @package    Grav\Common\Page
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Page\Interfaces;

interface PageCollectionInterface extends \Traversable, \ArrayAccess, \Countable, \Serializable
{
    /**
     * Get the collection params
     *
     * @return array
     */
    public function params();

    /**
     * Set parameters to the Collection
     *
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params);

    /**
     * Add a single page to a collection
     *
     * @param PageInterface $page
     *
     * @return $this
     */
    public function addPage(PageInterface $page);

    /**
     * Add a page with path and slug
     *
     * @param string $path
     * @param string $slug
     * @return $this
     */
    //public function add($path, $slug);

    /**
     *
     * Create a copy of this collection
     *
     * @return static
     */
    public function copy();

    /**
     *
     * Merge another collection with the current collection
     *
     * @param PageCollectionInterface $collection
     * @return $this
     */
    public function merge(PageCollectionInterface $collection);

    /**
     * Intersect another collection with the current collection
     *
     * @param PageCollectionInterface $collection
     * @return $this
     */
    public function intersect(PageCollectionInterface $collection);

    /**
     * Split collection into array of smaller collections.
     *
     * @param int $size
     * @return PageCollectionInterface[]
     */
    public function batch($size);

    /**
     * Remove item from the list.
     *
     * @param PageInterface|string|null $key
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    //public function remove($key = null);

    /**
     * Reorder collection.
     *
     * @param string $by
     * @param string $dir
     * @param array  $manual
     * @param string $sort_flags
     *
     * @return $this
     */
    public function order($by, $dir = 'asc', $manual = null, $sort_flags = null);

    /**
     * Check to see if this item is the first in the collection.
     *
     * @param  string $path
     *
     * @return bool True if item is first.
     */
    public function isFirst($path): bool;

    /**
     * Check to see if this item is the last in the collection.
     *
     * @param  string $path
     *
     * @return bool True if item is last.
     */
    public function isLast($path): bool;

    /**
     * Gets the previous sibling based on current position.
     *
     * @param  string $path
     *
     * @return PageInterface  The previous item.
     */
    public function prevSibling($path);

    /**
     * Gets the next sibling based on current position.
     *
     * @param  string $path
     *
     * @return PageInterface The next item.
     */
    public function nextSibling($path);

    /**
     * Returns the adjacent sibling based on a direction.
     *
     * @param  string  $path
     * @param  int $direction either -1 or +1
     *
     * @return PageInterface|PageCollectionInterface|false    The sibling item.
     */
    public function adjacentSibling($path, $direction = 1);

    /**
     * Returns the item in the current position.
     *
     * @param  string $path the path the item
     *
     * @return int|null The index of the current page, null if not found.
     */
    public function currentPosition($path): ?int;

    /**
     * Returns the items between a set of date ranges of either the page date field (default) or
     * an arbitrary datetime page field where end date is optional
     * Dates can be passed in as text that strtotime() can process
     * http://php.net/manual/en/function.strtotime.php
     *
     * @param string $startDate
     * @param bool $endDate
     * @param string|null $field
     *
     * @return $this
     * @throws \Exception
     */
    public function dateRange($startDate, $endDate = false, $field = null);

    /**
     * Creates new collection with only visible pages
     *
     * @return PageCollectionInterface The collection with only visible pages
     */
    public function visible();

    /**
     * Creates new collection with only non-visible pages
     *
     * @return PageCollectionInterface The collection with only non-visible pages
     */
    public function nonVisible();

    /**
     * Creates new collection with only modular pages
     *
     * @return PageCollectionInterface The collection with only modular pages
     */
    public function modular();

    /**
     * Creates new collection with only non-modular pages
     *
     * @return PageCollectionInterface The collection with only non-modular pages
     */
    public function nonModular();

    /**
     * Creates new collection with only published pages
     *
     * @return PageCollectionInterface The collection with only published pages
     */
    public function published();

    /**
     * Creates new collection with only non-published pages
     *
     * @return PageCollectionInterface The collection with only non-published pages
     */
    public function nonPublished();

    /**
     * Creates new collection with only routable pages
     *
     * @return PageCollectionInterface The collection with only routable pages
     */
    public function routable();

    /**
     * Creates new collection with only non-routable pages
     *
     * @return PageCollectionInterface The collection with only non-routable pages
     */
    public function nonRoutable();

    /**
     * Creates new collection with only pages of the specified type
     *
     * @param string $type
     *
     * @return PageCollectionInterface The collection
     */
    public function ofType($type);

    /**
     * Creates new collection with only pages of one of the specified types
     *
     * @param string[] $types
     *
     * @return PageCollectionInterface The collection
     */
    public function ofOneOfTheseTypes($types);

    /**
     * Creates new collection with only pages of one of the specified access levels
     *
     * @param array $accessLevels
     *
     * @return PageCollectionInterface The collection
     */
    public function ofOneOfTheseAccessLevels($accessLevels);

    /**
     * Converts collection into an array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Get the extended version of this Collection with each page keyed by route
     *
     * @return array
     * @throws \Exception
     */
    public function toExtendedArray();
}
