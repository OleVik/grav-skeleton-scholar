---
title: Index
---

The _Index_ Component displays one to two [collections of Pages](https://learn.getgrav.org/16/content/collections), the first one primary and the second complementary. The standard view is to render the main collection bigger and to the right on the Page:

![Index](image://breakpoints.spec.js/992/index.png)

The complementary collection will be smaller, and on the left, below the Page Title and Page Content. In the Page's Frontmatter, you can define one or both of these collections:

```yaml
title: Scholar
content:
  items:
    "@page.children": "/docs"
  limit: 10
aside:
  items:
    "@page.children": "/blog"
  order:
    by: date
    dir: desc
  limit: 10
logo: line-awesome-book.svg
```

If only the main-collection, `content`, is defined, only it is rendered:

![Index](image://breakpoints.spec.js/992/blog.png)

You can also include a logo above the Page Title, by using the `logo`-property and [linking it to an image](https://learn.getgrav.org/16/content/image-linking).
