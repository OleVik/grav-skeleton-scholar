<?php

/**
 * @package    Grav\Common\GPM
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\GPM\Common;

use Grav\Common\Iterator;

class CachedCollection extends Iterator
{
    /** @var array */
    protected static $cache = [];

    /**
     * CachedCollection constructor.
     *
     * @param array $items
     */
    public function __construct($items)
    {
        parent::__construct();

        $method = static::class . __METHOD__;

        // local cache to speed things up
        if (!isset(self::$cache[$method])) {
            self::$cache[$method] = $items;
        }

        foreach (self::$cache[$method] as $name => $item) {
            $this->append([$name => $item]);
        }
    }
}
