# Swiper Plugin

The **Swiper**-plugin is an extension for [Grav CMS](http://github.com/getgrav/grav) that provides a thin wrapper for [Swiper](https://swiperjs.com/), a "modern mobile touch slider with hardware accelerated transitions and amazing native behavior".

## Installation

Installing the Swiper-plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install swiper

This will install the Swiper-plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/swiper`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `swiper`. You can find these files on [GitHub](https://github.com/ole-vik/grav-plugin-swiper) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/swiper

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/swiper/swiper.yaml` to `user/config/plugins/swiper.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
templates: true
shortcodes: true
defaults:
  a11y:
    enabled: true
  autoHeight: true
  centeredSlides: true
  direction: horizontal
  keyboard:
    enabled: true
  navigation:
    nextEl: ".swiper-button-next"
    prevEl: ".swiper-button-prev"
  lazy: true
```

The `enabled`-option enables or disables the plugin, the `templates`-option enables or disables the plugin's templates, and the `shortcodes`-option enables or disables the plugin's shortcodes. The `defaults`-option sets the default settings for Swiper.

Note that if you use the Admin Plugin, a file with your configuration named swiper.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

The plugin exposes a `[slider]`-shortcode, a Page Type and a template-partial. These can be overriden by your theme or plugin.

- The shortcode can take all options of the [Swiper API](https://swiperjs.com/api) through its parameters
    - Options are set with the dot-notation format
    - Options with no explicit value are interpreted as `true`
- Slides can contain any content that Swiper accepts, and each Slide within `[slider]` and `[/slider]` must be separated by a new line
- Assets are loaded by default
- Default settings can be set with the `defaults` in `/user/config/plugins/swiper.yaml`

### Example

```markdown
[slider a11y.enabled autoHeight="true"]

![Image 1](image1.jpg)

![Image 1](image2.jpg)

![Image 1](image3.jpg)

[/slider]
```
