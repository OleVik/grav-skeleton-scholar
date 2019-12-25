---
title: Development
---

The scripts in `package.json` cover most front-end development tasks:

- `theme`: Watches and compiles the common CSS for the theme's structure
- `base`: Watches and compiles all Styles if the base changes
- `styles`: Watches and compiles each Style if it changes
- `print`: Compiles Print-CSS
- `css`: Run all of the above at once
- `scripts:babel`: Compile JS with Babel
- `scripts:watch`: Watch and compile JS with Parcel
- `scripts:build`: Compile JS with Parcel
- `test`: Test front-end accessibility with Cypress

All of these requires development-packages being installed through `npm install`. PostCSS is used for compiling all CSS. The source for CSS- and JS-files are in `/src`.

## PHP Code Standards

This plugin follows PSR-1, PSR-2, and PEAR coding standards, as well as PSR-4.

### Extending

As demonstrated by the API-options, you can fairly easily extend the PHP-behavior of the plugin. Extensions to the theme's API must use the namespace `Grav\Theme\Scholar`.

## Customizing blueprints

Your Theme or Skeleton can extend or copy from the blueprints in `/blueprints/partials` to create custom blueprints.
