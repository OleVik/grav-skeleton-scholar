<?php
/**
 * Swiper Plugin
 *
 * PHP version 7
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage Swiper
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-swiper
 */
namespace Grav\Plugin;

use Grav\Common\Plugin;

/**
 * Swiper Plugin
 *
 * Class Swiper
 *
 * @category Extensions
 * @package  Grav\Theme
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-swiper
 */
class SwiperPlugin extends Plugin
{
    /**
     * Register intial event
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin and events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }
        if ($this->config->get('plugins.swiper.enabled') != true) {
            return;
        }
        $this->enable(
            [
                'onShortcodeHandlers' => ['shortcodes', 0],
                'onTwigTemplatePaths' => ['templates', 0],
                'onAssetsInitialized' => ['assets', 0]
            ]
        );
    }

    /**
     * Register templates
     *
     * @return void
     */
    public function templates()
    {
        if ($this->config->get('plugins.swiper.templates')) {
            $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
        }
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function shortcodes()
    {
        if ($this->config->get('plugins.swiper.shortcodes')) {
            $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
        }
    }

    /**
     * Register assets
     *
     * @return void
     */
    public function assets()
    {
        if ($this->config->get('plugins.swiper.css')) {
            $this->grav['assets']->addCss(
                'plugins://swiper/node_modules/swiper/css/swiper.min.css'
            );
        }
        if ($this->config->get('plugins.swiper.js')) {
            $this->grav['assets']->addJs(
                'plugins://swiper/node_modules/swiper/js/swiper.min.js'
            );
        }
    }
}
