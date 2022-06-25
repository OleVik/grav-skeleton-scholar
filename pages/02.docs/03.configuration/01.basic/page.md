---
title: Basic
---

## Enabled (boolean)

Default: `true`, enables theme. `false` disables theme.

## Style (string)

Default: `metal`, enables the Metal Style. Choose the filename, excluding its extinction, of another Style from `/css/styles`.

## Toolbar (object)

### Breadcrumbs (boolean)

Default: `true`, enables breadcrumbs in toolbar. `false` disables it.

## Search (boolean)

Default: `true`, enables search-field in toolbar. `false` disables it.

### Navigation (boolean)

Default: `true`, enables navigation-drawer in toolbar. `false` disables it.

## CSS (boolean)

Default: `true`, enables theme CSS. `false` disables it.

## JS (boolean)

Default: `true`, enables theme JS. `false` disables it.

## Itemize (boolean)

Default: `true`, enables itemization. `false` disables it. This assigns numerical indices to paragraphs in block text.

## Linked Data (boolean)

Default: `true`, enables generation of Linked Data. `false` disables it. Linked Data optimizes rendering in search engine results, and enables easy microdata-interaction.

## FlexSearch (object)

Default:

```yaml
enabled: true
index:
  limit: 10
  profile: speed
  encode: icase
  tokenize: strict
  cache: true
  async: true
full:
  limit: 10
  profile: balance
  encode: advanced
  tokenize: full
  cache: true
  async: true
```

FlexSearch is a highly performant search engine, used by the theme for optimal static searching of content and metadata. The `index`-key sets [options](https://github.com/nextapps-de/flexsearch#options) for searching metadata, and the `full`-key sets them for searching content and metadata. Neither have blueprint-implementations in Admin, edit the configuration-file directly.

### Enabled (boolean)

Default: `true`, enables FlexSearch. `false` disables it.
