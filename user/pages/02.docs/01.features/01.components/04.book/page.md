---
title: Book
---

The _Book_ Component is a simple structure for rendering a book in its entirety. Like other components, the Book-template will be at the top of the hierarchy of Pages. Following that, you can group chapters in their own folders and use the Chapter-template, and below that each Page will have its own folder and use the Page-template.

## An example

The [sample Book](https://github.com/OleVik/grav-skeleton-scholar/tree/master/user/pages/07.book), "The Hound of the Baskervilles" by Sir Arthur Conan Doyle, has the following structure:

```
user/pages/07.book
|   book.md
|   cover.jpg
|
+---01.mr-sherlock-holmes
|   |   chapter.md
|   |
|   +---1.1
|   |       page.md
|   |
|   +---2.2
|   |       page.md
|   |
|   +---3.3
|   |       page.md
|   |
|   +---4.4
|   |       page.md
|   |
|   +---5.5
|   |       page.md
|   |
|   +---6.6
|   |       page.md
|   |
|   +---7.7
|   |       page.md
|   |
|   +---8.8
|   |       page.md
|   |
|   \---9.9
|           page.md
...
```

Both the Book and Chapter can use the `subtitle`-property, but otherwise requires very little metadata in the FrontMatter. Pages will be grouped and rendered by the templates. Only the `book.md`- and `chapter.md`-files need to contain FrontMatter, all `page.md`-files can only Markdown, as the Page-number within the Chapter will be used as the title.

## Ordering Pages in the Book

It is important that you explicitly change how Pages should be ordered, if you are not utilising [Grav's natural ordering](https://learn.getgrav.org/16/content/content-pages#folders). This looks at the folder-names and checks whether any integer follow by a period, eg. `01.folder-name`, is used. This is a simple way to ensure that Chapters and Pages appear in the order you expect them to. But there are many other [ordering options](https://learn.getgrav.org/16/content/collections#ordering-options) that can be used, just pass the parameters in the FrontMatter:

```yaml
content:
  order:
    by: folder
    dir: asc
```

## Book Cover

By setting the `image`-property in the FrontMatter, you can link to an image to display as the Book Cover. You can also assign taxonomies as usual, and specify a print-logic for the special `/print`-route.

```yaml
title: "The Hound of the Baskervilles"
menu: "Book"
image: "cover.jpg"
taxonomy:
  categories: fiction
  tags: [book]
  author:
    - name: "Sir Arthur Conan Doyle"
      affiliation: "Farkas Translations, ManyBooks.net"
print:
  items: "@self.descendants"
  order:
    by: 'folder'
    dir: 'asc'
  process: true
```

![Index](image://breakpoints.spec.js/992/book.png)
