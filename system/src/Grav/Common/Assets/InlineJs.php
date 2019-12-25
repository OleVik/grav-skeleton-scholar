<?php

/**
 * @package    Grav\Common\Assets
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Assets;

use Grav\Common\Utils;

class InlineJs extends BaseAsset
{
    /**
     * InlineJs constructor.
     * @param array $elements
     * @param string|null $key
     */
    public function __construct(array $elements = [], $key = null)
    {
        $base_options = [
            'asset_type' => 'js',
            'position' => 'after'
        ];

        $merged_attributes = Utils::arrayMergeRecursiveUnique($base_options, $elements);

        parent::__construct($merged_attributes, $key);
    }

    /**
     * @return string
     */
    public function render()
    {
        return '<script' . $this->renderAttributes(). ">\n" . trim($this->asset) . "\n</script>\n";
    }
}
