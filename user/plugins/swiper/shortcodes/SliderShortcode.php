<?php
/**
 * Scholar Theme
 *
 * PHP version 7
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage Scholar
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-theme-scholar
 */
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Create a sidenote for Tufte CSS
 *
 * Class ScholarTheme
 *
 * @category Extensions
 * @package  Grav\Plugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-theme-scholar
 */
class SliderShortcode extends Shortcode
{
    /**
     * Initialize shortcode
     *
     * @return string
     */
    public function init()
    {
        $this->shortcode->getHandlers()->add(
            'slider',
            function (ShortcodeInterface $sc) {
                return $this->slideRenderer($sc, 'slider');
            }
        );
    }

    /**
     * Render a sidenote in HTML
     *
     * @param ShortcodeInterface $sc   Accessor to Thunder\Shortcode
     * @param string             $type Type of note
     *
     * @return string
     */
    public function slideRenderer(ShortcodeInterface $sc, string $type)
    {
        $language = Grav::instance()['language'];
        $content = $sc->getParameter('content', $sc->getContent());
        $defaults = Grav::instance()['config']->get('plugins.swiper.defaults');
        $defaults = array_merge(
            $defaults,
            [
                'a11y' => [
                    'prevSlideMessage' => ucfirst(
                        $language->translate(['THEME_SCHOLAR.GENERIC.PREVIOUS'])
                    ),
                    'nextSlideMessage' => ucfirst(
                        $language->translate(['THEME_SCHOLAR.GENERIC.NEXT'])
                    )
                ]
            ]
        );
        $parameters = Utils::arrayUnflattenDotNotation($sc->getParameters());
        foreach ($parameters as $key => $value) {
            if ($value === null) {
                $parameters[$key] = true;
            } elseif ($value === "true") {
                $parameters[$key] = true;
            } elseif ($value === "false") {
                $parameters[$key] = false;
            }
        }
        $parameters = array_merge($defaults, $parameters);
        $id = Utils::generateRandomString(24);
        $output = $this->twig->processTemplate(
            'partials/slider.html.twig',
            [
                'id' => $id,
                'content' => self::rewrap($content),
                'params' => $parameters
            ]
        );
        $this->shortcode->addAssets(
            'inlineJs',
            'var Swiper_' . $id . ' = new Swiper("#' . $id . '", ' . json_encode($parameters) . ');'
        );
        return $output;
    }

    /**
     * Replace paragraphs with .swiper-slide
     *
     * @param string $content Content to process
     *
     * @return string Processed content
     */
    public static function rewrap(string $content): string
    {
        return str_replace(
            ['<p>', '</p>'],
            ['<div class="swiper-slide">', '</div>'],
            $content
        );
    }
}
