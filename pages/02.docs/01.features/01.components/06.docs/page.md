---
title: Docs
---

The _Docs_ Component is made for creating a structured set of Pages for documentation. Like most other Components, you only use the Docs-template at the top of file-structure, as seen in the [sample Pages](https://github.com/OleVik/grav-skeleton-scholar/tree/master/user/pages/02.docs):

```
02.docs
|   docs.md
|   screenshot.jpg
+---01.features
|   |   page.md
|   +---01.components
|   |   |   page.md
|   +---02.layouts
|   |       page.md
|   +---03.styles
|   |   |   page.md
|   +---04.performance
|   |   |   page.md
|   +---05.accessibility
|   |   |   page.md
|   \---06.dynamic-routes
|           page.md
+---02.installation
|       page.md
+---03.configuration
|   |   page.md
+---04.getting-started
|   |   page.md
\---05.development
        page.md
```

All Pages below this will use the Page-template. For each Page you can set the [collection](https://learn.getgrav.org/16/content/collections)-property to tell the Page what Page(s) to display. This only comes into effect when there is no content in the Page, and it will default to a ["Listing Page"](https://learn.getgrav.org/16/content/content-pages#listing-page). This Listing Page will first look for Child-Pages, then Page-siblings, to have something to display. If nothing is found, the Page will be blank except for the phrase "This page intentionally left blank."

A typical FrontMatter of a Docs Page will look like:

```yaml
title: Documentation
menu: Documentation
content:
  items: "@page.children"
  order:
    by: date
    dir: desc
  limit: 10
  pagination: false
theme:
  itemize: false
```

![Index](image://breakpoints.spec.js/992/docs.png)
