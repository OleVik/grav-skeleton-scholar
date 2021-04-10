# Scholar Skeleton

Scholar is an academic-focused theme, for publishing papers, articles, books, documentation, your blog, and even your resumé, with [Grav](https://getgrav.org/).

## Features

- Extensible Components, Layouts and template-partials, Styles, API
- Responsive Layouts, multiple Styles
  - Print-friendly styles
- Performant, light on resources
- Accessible, tested against WCAG AA, Section 508, and best practices
  - Navigable by keyboard and screen readers
  - Readable contrast across Styles
  - Clean, declarative HTML-structure with semantic labels
- Automated Evergreen-browser compatibility
- Compatible with a static setup
- Dynamic functionality for Data, Embed, Print, and Search Pages

A demonstration is available at [OleVik.me/staging/grav-skeleton-scholar](https://olevik.me/staging/grav-skeleton-scholar), and its full contents are in this repository.

## Usage

### Configuration

| Option              | Default   | Description                                   |
|---------------------|-----------|-----------------------------------------------|
| enabled             | true      | Enable theme                                  |
| style               | metal     | Default Style to load                         |
| toolbar.breadcrumbs | true      | Enable breadcrumbs in toolbar                 |
| toolbar.search      | true      | Enable search-field in toolbar                |
| toolbar.navigation  | true      | Enable navigation-drawer in toolbar           |
| css                 | true      | Load theme's CSS                              |
| js                  | true      | Load theme's JS                               |
| itemize             | true      | Assign indices to paragraphs                  |
| linked_data         | true      | Generated Linked Data                         |
| highlighter         | true      | Highlight code                                |
| highlighter_theme   | enlighter | Theme for highlighter                         |
| components          | [List]    | List of components to enable                  |
| router              | true      | Enable dynamic routes                         |
| routes              | [Dict]    | Key-value list of routes                      |
| api                 | [Dict]    | Hierarchical key-value list of classes to use |
| flexsearch          | [Dict]    | Options for FlexSearch                        |
| flexsearch.enabled  | true      | Enable FlexSearch                             |

## Installation

**NOTE:** Your installation of PHP must satisfy the version constraint `>=7.1.3 <7.4`, as Grav and its dependencies currently suffers errors from incompatibilities in the ecosystem.

## Manual Installation

To install this skeleton, just download the zip version of this repository and unzip it under `/your/site/grav`.

You should now have all the theme files under

    /your/site/grav/user/themes/scholar
