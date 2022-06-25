---
title: Performance
---

Though the theme is written to be lightweight, modular and restrictive in what operations it performs under the hood, it is not heavily optimized to achieve a high PageRank or the like. This is because the website administrator should optimize resources in accordance with the server the website is ran on.

Grav's documentation has many tips on [improving performance](https://learn.getgrav.org/16/advanced/performance-and-caching) and [security](https://learn.getgrav.org/16/security/server-side). The most important thing you can do to improve the speed of Grav is to run a modern version of PHP, on a server with solid-state drives. To improve the speed of the theme, make sure the site runs over HTTPS and uses the [HTTP/2 protocol](https://developers.google.com/web/fundamentals/performance/http2) - this massively improves how fast assets are downloaded. Finally, consider using a [content delivery network and load-balancer](https://support.cloudflare.com/hc/en-us/articles/205177068-How-does-Cloudflare-work-).

Even without optimization beyond Grav's default file caching, the above improvements should have the website load in some hundreds of milliseconds. A guide on achieving close to 100% score in PageRank and YSlow can be [found online](https://olevik.me/writing/code/optimizing-a-grav-installation).
