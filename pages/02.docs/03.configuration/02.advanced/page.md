---
title: Advanced
---

## Components (array)

Default:

```yaml
- blog
- book
- cv
- docs
- tufte
```

Enables components from `/components`.

## Router (boolean)

Default: `true`, enables dynamic routing. `false` disables it. Dynamic routes lets the theme handle the rendering of content in certain suffixed URLs.

## Routes (object)

Default:

```yaml
data: "/data"
embed: "/embed"
search: "/search"
print: "/print"
```

- Data: Route suffixed to Page-route to retrieve JSON-data.
- Embed: Route suffixed to Page-route to retrieve embeddable HTML.
- Search: Route for virtual Page for searching.
- Print: Route suffixed to Page-route to render its "print"-collection.
