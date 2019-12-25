<?php

declare(strict_types=1);

/**
 * @package    Grav\Common\Flex
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Flex\Pages\Traits;

use Grav\Common\Grav;
use Grav\Common\Language\Language;
use Grav\Common\Page\Page;
use Grav\Common\Utils;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Implements PageTranslateInterface
 */
trait PageTranslateTrait
{
    /**
     * Return an array with the routes of other translated languages
     *
     * @param bool $onlyPublished only return published translations
     * @return array the page translated languages
     */
    public function translatedLanguages($onlyPublished = false): array
    {
        if (Utils::isAdminPlugin()) {
            return parent::translatedLanguages();
        }

        $translated = $this->getLanguageTemplates();
        if (!$translated) {
            return $translated;
        }

        $grav = Grav::instance();

        /** @var Language $language */
        $language = $grav['language'];

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        $languages = $language->getLanguages();
        $languages[] = '';
        $defaultCode = $language->getDefault();

        if (isset($translated[$defaultCode])) {
            unset($translated['']);
        }

        foreach ($translated as $key => &$template) {
            $template .= $key !== '' ? ".{$key}.md" : '.md';
        }
        unset($template);

        $translated = array_intersect_key($translated, array_flip($languages));

        $folder = $this->getStorageFolder();
        if (!$folder) {
            return [];
        }
        $folder = $locator($folder);

        $list = array_fill_keys($languages, null);
        foreach ($translated as $languageCode => $languageFile) {
            $languageExtension = $languageCode ? ".{$languageCode}.md" : '.md';
            $path = "{$folder}/{$languageFile}";

            // FIXME: use flex, also rawRoute() does not fully work?
            $aPage = new Page();
            $aPage->init(new \SplFileInfo($path), $languageExtension);
            if ($onlyPublished && !$aPage->published()) {
                continue;
            }

            $header = $aPage->header();
            $routes = isset($header->routes) ? $header->routes : [];
            $route = $routes['default'] ?? $aPage->rawRoute();
            if (!$route) {
                $route = $aPage->route();
            }

            $list[$languageCode ?: $defaultCode] = $route ?? '';
        }

        return array_filter($list, static function ($var) {
            return null !== $var;
        });
    }
}
