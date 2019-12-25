<?php

/**
 * @package    Grav\Common\Assets
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Assets;

use Grav\Common\Assets\Traits\AssetUtilsTrait;
use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Utils;
use Grav\Framework\Object\PropertyObject;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Pipeline extends PropertyObject
{
    use AssetUtilsTrait;

    protected const CSS_ASSET = true;
    protected const JS_ASSET = false;

    /** @const Regex to match CSS urls */
    protected const CSS_URL_REGEX = '{url\(([\'\"]?)(.*?)\1\)}';

    /** @const Regex to match CSS sourcemap comments */
    protected const CSS_SOURCEMAP_REGEX = '{\/\*# (.*?) \*\/}';

    /** @const Regex to match CSS import content */
    protected const CSS_IMPORT_REGEX = '{@import(.*?);}';

    protected const FIRST_FORWARDSLASH_REGEX = '{^\/{1}\w}';

    // Following variables come from the configuration:
    /** @var bool */
    protected $css_minify = false;
    /** @var bool */
    protected $css_minify_windows = false;
    /** @var bool */
    protected $css_rewrite = false;
    /** @var bool */
    protected $css_pipeline_include_externals = true;
    /** @var bool */
    protected $js_minify = false;
    /** @var bool */
    protected $js_minify_windows = false;
    /** @var bool */
    protected $js_pipeline_include_externals = true;

    /** @var string */
    protected $assets_dir;
    /** @var string */
    protected $assets_url;
    /** @var string */
    protected $timestamp;
    /** @var array */
    protected $attributes;
    /** @var string */
    protected $query = '';
    /** @var string */
    protected $asset;

    /**
     * Pipeline constructor.
     * @param array $elements
     * @param string|null $key
     */
    public function __construct(array $elements = [], ?string $key = null)
    {
        parent::__construct($elements, $key);

        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];

        /** @var Config $config */
        $config = Grav::instance()['config'];

        /** @var Uri $uri */
        $uri = Grav::instance()['uri'];

        $this->base_url = rtrim($uri->rootUrl($config->get('system.absolute_urls')), '/') . '/';
        $this->assets_dir = $locator->findResource('asset://') . DS;
        $this->assets_url = $locator->findResource('asset://', false);
    }

    /**
     * Minify and concatenate CSS
     *
     * @param array $assets
     * @param string $group
     * @param array $attributes
     * @return bool|string     URL or generated content if available, else false
     */
    public function renderCss($assets, $group, $attributes = [])
    {
        // temporary list of assets to pipeline
        $inline_group = false;

        if (array_key_exists('loading', $attributes) && $attributes['loading'] === 'inline') {
            $inline_group = true;
            unset($attributes['loading']);
        }

        // Store Attributes
        $this->attributes = array_merge(['type' => 'text/css', 'rel' => 'stylesheet'], $attributes);

        // Compute uid based on assets and timestamp
        $json_assets = json_encode($assets);
        $uid = md5($json_assets . $this->css_minify . $this->css_rewrite . $group);
        $file = $uid . '.css';
        $relative_path = "{$this->base_url}{$this->assets_url}/{$file}";

        $buffer = null;

        if (file_exists($this->assets_dir . $file)) {
            $buffer = file_get_contents($this->assets_dir . $file) . "\n";
        } else {
            //if nothing found get out of here!
            if (empty($assets)) {
                return false;
            }

            // Concatenate files
            $buffer = $this->gatherLinks($assets, self::CSS_ASSET);

            // Minify if required
            if ($this->shouldMinify('css')) {
                $minifier = new \MatthiasMullie\Minify\CSS();
                $minifier->add($buffer);
                $buffer = $minifier->minify();
            }

            // Write file
            if (trim($buffer) !== '') {
                file_put_contents($this->assets_dir . $file, $buffer);
            }
        }

        if ($inline_group) {
            $output = "<style>\n" . $buffer . "\n</style>\n";
        } else {
            $this->asset = $relative_path;
            $output = '<link href="' . $relative_path . $this->renderQueryString() . '"' . $this->renderAttributes() . ">\n";
        }

        return $output;
    }

    /**
     * Minify and concatenate JS files.
     *
     * @param array $assets
     * @param string $group
     * @param array $attributes
     * @return bool|string     URL or generated content if available, else false
     */
    public function renderJs($assets, $group, $attributes = [])
    {
        // temporary list of assets to pipeline
        $inline_group = false;

        if (array_key_exists('loading', $attributes) && $attributes['loading'] === 'inline') {
            $inline_group = true;
            unset($attributes['loading']);
        }

        // Store Attributes
        $this->attributes = $attributes;

        // Compute uid based on assets and timestamp
        $json_assets = json_encode($assets);
        $uid = md5($json_assets . $this->js_minify . $group);
        $file = $uid . '.js';
        $relative_path = "{$this->base_url}{$this->assets_url}/{$file}";

        $buffer = null;

        if (file_exists($this->assets_dir . $file)) {
            $buffer = file_get_contents($this->assets_dir . $file) . "\n";
        } else {
            //if nothing found get out of here!
            if (empty($assets)) {
                return false;
            }

            // Concatenate files
            $buffer = $this->gatherLinks($assets, self::JS_ASSET);

            // Minify if required
            if ($this->shouldMinify('js')) {
                $minifier = new \MatthiasMullie\Minify\JS();
                $minifier->add($buffer);
                $buffer = $minifier->minify();
            }

            // Write file
            if (trim($buffer) !== '') {
                file_put_contents($this->assets_dir . $file, $buffer);
            }
        }

        if ($inline_group) {
            $output = '<script' . $this->renderAttributes(). ">\n" . $buffer . "\n</script>\n";
        } else {
            $this->asset = $relative_path;
            $output = '<script src="' . $relative_path . $this->renderQueryString() . '"' . $this->renderAttributes() . "></script>\n";
        }

        return $output;
    }


    /**
     * Finds relative CSS urls() and rewrites the URL with an absolute one
     *
     * @param string $file the css source file
     * @param string $dir , $local relative path to the css file
     * @param bool $local is this a local or remote asset
     * @return mixed
     */
    protected function cssRewrite($file, $dir, $local)
    {
        // Strip any sourcemap comments
        $file = preg_replace(self::CSS_SOURCEMAP_REGEX, '', $file);

        // Find any css url() elements, grab the URLs and calculate an absolute path
        // Then replace the old url with the new one
        $file = (string)preg_replace_callback(self::CSS_URL_REGEX, function ($matches) use ($dir, $local) {

            $old_url = $matches[2];

            // Ensure link is not rooted to web server, a data URL, or to a remote host
            if (preg_match(self::FIRST_FORWARDSLASH_REGEX, $old_url) || Utils::startsWith($old_url, 'data:') || $this->isRemoteLink($old_url)) {
                return $matches[0];
            }

            // clean leading /
            $old_url = Utils::normalizePath($dir . '/' . $old_url);
            if (preg_match(self::FIRST_FORWARDSLASH_REGEX, $old_url)) {
                $old_url = ltrim($old_url, '/');
            }

            $new_url = ($local ? $this->base_url: '') . $old_url;

            return str_replace($matches[2], $new_url, $matches[0]);
        }, $file);

        return $file;
    }

    /**
     * @param string $type
     * @return bool
     */
    private function shouldMinify($type = 'css')
    {
        $check = $type . '_minify';
        $win_check = $type . '_minify_windows';

        $minify = (bool) $this->$check;

        // If this is a Windows server, and minify_windows is false (default value) skip the
        // minification process because it will cause Apache to die/crash due to insufficient
        // ThreadStackSize in httpd.conf - See: https://bugs.php.net/bug.php?id=47689
        if (stripos(php_uname('s'), 'WIN') === 0 && !$this->{$win_check}) {
            $minify = false;
        }

        return $minify;
    }
}
