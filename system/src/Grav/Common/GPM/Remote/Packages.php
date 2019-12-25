<?php

/**
 * @package    Grav\Common\GPM
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\GPM\Remote;

use Grav\Common\GPM\Common\CachedCollection;

class Packages extends CachedCollection
{
    /**
     * Packages constructor.
     * @param bool $refresh
     * @param callable|null $callback
     */
    public function __construct($refresh = false, $callback = null)
    {
        $items = [
            'plugins' => new Plugins($refresh, $callback),
            'themes' => new Themes($refresh, $callback)
        ];

        parent::__construct($items);
    }
}
