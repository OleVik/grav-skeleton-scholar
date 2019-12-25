---
title: Expert
---

## API (object)

Default:

```yaml
content: 'Content\Content'
linked_data:
  default: 'LinkedData\PageLinkedData'
  cv: 'LinkedData\CVLinkedData'
router: 'Router\Router'
source: 'Source\Source'
taxonomy_map: 'TaxonomyMap\TaxonomyMap'
```

Classes used by API, all namedspaced to `Grav\Theme\Scholar`.

### Content (string)

Class used for manipulating HTML, largely static methods.

### Linked Data (object)

Class used for generating [Linked Data](https://json-ld.org/).

#### Default (string)

Default class used for generating Linked Data.

#### CV (string)

Class used for generating Linked Data for the CV Page Type.

### Router (string)

Class used for dynamic routes, that lets the theme handle the rendering of content in certain suffixed URLs.

### Source (string)

Helper-class used for parsing and cleaning Media-paths in a Page.

### Taxonomy Map (string)

Helper-class for generating a structured mapping of the Taxonomy used on a site, and the taxonomical items therein.
