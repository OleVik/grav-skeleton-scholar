---
title: Components
---

The theme includes some core, and some modular, Page Types. These are templates that each Page can use, and the core types include:

- Default: A generic type
- Index: A type for displaying a collection of Pages
- Listing: A more generic type for displaying a collection of Pages
- Article: A template for an article, a paper, a document, or a similar type of standalone piece of writing
- Page: A generic type which extends the topmost parent's template
- Print: A helper-type for adopting the rendering for printing
- Search: A helper-type which extends the topmost parent's template and implements search-fields

The modular types include:

- Blog: A replica of `Index`, but includes the `blog`-class for styling-differences
- Post: A subset of `Default`, but includes the option for a hero-image
- Book: A type for rendering a collections of Pages, with rendering optimized for a book's contents
- Chapter: A replica of `Page`, used for organization of Pages
- CV: A type for rendering a standalone resumé
- Docs: A type for rendering Documentation Pages
- Sequence: An experimentational type for rendering a structured diagram of Pages
- Tufte: A subset of `Article`, optimized for [Tufte Handouts](https://edwardtufte.github.io/tufte-css/)

## Components and Page Types

The theme is stricter than most themes in how the Pages and Page Types must be structured. The theme expects Page Types to be declared as high-level structures, with lower-level structures beneath. For example, for a set of Documentation Pages, `docs.html.twig` would be the uppermost template. Below there will be hierarchical folders of Pages, using `page.html.twig` for each.

The theme uses modular components to let you choose what features you want. These are not the same as as [Modular Pages](https://learn.getgrav.org/16/content/modular) in Grav. The `components`-setting in the theme's configuration-file is a plain list of names of Components to load.

Each Component exists in the `/components`-folder, and contains needed templates, a schema, and assets needed to render it. Extensions to the theme, or child-themes, can deliver their own Components by replicating this structure or overriding the existing structure. For example, the Tufte-article looks like this, in `/components/tufte`:

```
│  schema.yaml
│  tufte.html.twig
├──assets/
│    tufte.min.css
├──partials/
│   └──tufte
│        note.html.twig
```

Wherein `schema.yaml` holds basic data used for Linked Data and ARIA-attributes:

```yaml
tufte:
  name: tufte
  schema: ScholarlyArticle
```

`tufte.html.twig` defines how a `tufte.md`-file is rendered, `/components/tufte/assets` holds the necessary style in `tufte.min.css`, `/components/tufte/partials` holds template-pieces specific to this template, and `/components/tufte/shortcodes` the shortcodes that can be used in `tufte.md`.

## Notes

Several types of notes are supported: Endnotes through Markdown Extra, as well as margin- and side-notes through the `[marginnote]`- and `[sidenote]`-shortcodes. Side-notes are numbered whilst margin-notes are not, and they rely on relatively uninform CSS styles. Each of them can be used within paragraphs or by themselves, and will render to the right of the normal text.
