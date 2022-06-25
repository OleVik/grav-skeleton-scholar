---
title: Styles
---

_Styles_ are Cascading Style Sheets (CSS) that stylize all aspects of the basic structure of the Scholar-theme. They are, however, not defined within Components, but rather in CSS-files available to the theme itself. This is to reduce the typical overhead of loading more files with each Page.

Styles follow a basic principle: They should only apply color, fonts, or other rules that accentuate elements. The basic structure and layout of each Page is governed by the theme itself. This makes it easy to use the theme as a base for creating your own Style, as `/css/theme.css` will structure each Page uniformly with the default styles set by the browser.

You can set the type of Style to use on any specific Page, by using the `theme`-property in the Page's FrontMatter:

```yaml
theme:
  style: metal
```

So if you wanted to use the Berlin Style for a Page, you would set `theme: style: berlin`. This option also cascades from top-level Pages to lower-level Pages. For example, if the Docs Page sets a specific Style in it's FrontMatter, all descendant Pages will use the same Style, unless any specific Page sets its own Style to use.

The default theme is named _Metal_, and looks like this:

## Metal

### Index

![Body](image://styles.spec.js/metal/index/body.png)

### Article

![Body](image://styles.spec.js/metal/article/body.png)

### Blog

![Body](image://styles.spec.js/metal/blog/body.png)

### CV

![Body](image://styles.spec.js/metal/cv/body.png)

### Docs

![Body](image://styles.spec.js/metal/docs/body.png)
